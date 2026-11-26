<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SmartNotificationService;
use App\Services\UserAppCampaignDispatcher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SmartNotificationController extends Controller
{
    public function index(Request $request, SmartNotificationService $notifications)
    {
        return view('admin.extras.smart-notifications', [
            'stats' => $notifications->statistics(),
            'campaigns' => $notifications->campaignPage($request->only(['status', 'category', 'target_type', 'from', 'to', 'q'])),
            'filters' => $request->only(['status', 'category', 'target_type', 'from', 'to', 'q']),
            'schemaReady' => $notifications->schemaReady(),
            'categories' => SmartNotificationService::CATEGORIES,
        ]);
    }

    public function show(int $id, SmartNotificationService $notifications)
    {
        $campaign = $notifications->campaign($id);
        if (! $campaign) {
            return redirect()->route('smartNotifications')->with('error', 'That campaign was not found.');
        }

        return view('admin.extras.smart-notification-show', [
            'campaign' => $campaign,
            'recipients' => $notifications->recipientPage($id),
            'audience' => $notifications->audienceLabel([
                'target_type' => $campaign->target_type,
                'target_role_id' => $campaign->target_role_id,
            ]),
        ]);
    }

    public function cancel(int $id, SmartNotificationService $notifications)
    {
        $campaign = $notifications->campaign($id);
        if (! $campaign || ! in_array($campaign->status, ['draft', 'scheduled'], true)) {
            return redirect()->route('smartNotifications')->with('error', 'Only a draft or a scheduled campaign can be cancelled. A notification that was already sent cannot be taken back.');
        }
        DB::table('notification_campaigns')->where('id', $id)->whereIn('status', ['draft', 'scheduled'])->update([
            'status' => 'cancelled',
            'completed_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        DB::table('notification_campaign_events')->insert([
            'campaign_id' => $id,
            'actor_id' => (int) session('user_id'),
            'action' => 'cancelled',
            'note' => 'cancelled before send',
            'created_at' => Carbon::now(),
        ]);

        return redirect()->route('smartNotificationShow', $id)->with('status', 'Campaign '.$id.' was cancelled. Nothing already delivered can be recalled.');
    }

    public function retry(int $id, SmartNotificationService $notifications, UserAppCampaignDispatcher $dispatcher)
    {
        $adminId = (int) session('user_id');
        $key = 'smart-notification:'.$adminId;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return redirect()->route('smartNotificationShow', $id)->with('error', 'Too many attempts. Wait a minute and try again.');
        }
        $campaign = $notifications->campaign($id);
        if (! $campaign || ! in_array($campaign->status, ['failed', 'partially_failed'], true)) {
            return redirect()->route('smartNotificationShow', $id)->with('error', 'Only failed deliveries can be retried. Successful recipients are not sent again.');
        }
        $pending = (int) DB::table('messages')->where('campaign_id', $id)->whereIn('status', [0, 3])->count();
        if ($pending < 1) {
            return redirect()->route('smartNotificationShow', $id)->with('error', 'There is no failed recipient left to retry.');
        }
        RateLimiter::hit($key, 60);
        $result = $dispatcher->send($id, true);
        if (! $result['ok']) {
            return redirect()->route('smartNotificationShow', $id)->with('error', 'Retry did not start. '.$result['output']);
        }

        return redirect()->route('smartNotificationShow', $id)->with('status', 'Retry started for campaign '.$id.'. Recipients who already received it are skipped.');
    }

    public function create()
    {
        return view('admin.extras.smart-notification-compose', [
            'categories' => SmartNotificationService::CATEGORIES,
        ]);
    }

    public function preview(Request $request, SmartNotificationService $notifications)
    {
        if (! $notifications->schemaReady()) {
            return redirect()->route('smartNotifications')->with('error', 'The notification tables are not on this database yet. Run the campaign migration before creating a campaign.');
        }
        $input = $this->validated($request, $notifications);
        $summary = $notifications->audienceSummary($input);
        $uuid = (string) Str::uuid();
        $request->session()->put('smart_notification_draft', [
            'uuid' => $uuid,
            'admin_id' => (int) session('user_id'),
            'input' => $input,
            'summary' => $summary,
            'saved_at' => Carbon::now()->toDateTimeString(),
        ]);

        return view('admin.extras.smart-notification-confirm', [
            'draft' => $request->session()->get('smart_notification_draft'),
            'audience' => $notifications->audienceLabel($input),
            'userLabel' => $input['target_type'] === 'user' ? $notifications->userLabel((int) $input['target_user_id']) : null,
            'consentApplies' => $notifications->requiresConsent($input['category']),
            'categoryLabel' => SmartNotificationService::CATEGORIES[$input['category']] ?? $input['category'],
        ]);
    }

    public function confirm(Request $request, SmartNotificationService $notifications, UserAppCampaignDispatcher $dispatcher)
    {
        $adminId = (int) session('user_id');
        $key = 'smart-notification:'.$adminId;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return redirect()->route('smartNotificationCreate')->with('error', 'Too many campaign confirmations. Wait a minute and try again.');
        }

        $draft = $request->session()->get('smart_notification_draft');
        if (! is_array($draft) || (int) ($draft['admin_id'] ?? 0) !== $adminId || (string) ($draft['uuid'] ?? '') === '') {
            return redirect()->route('smartNotificationCreate')->with('error', 'The confirmation expired. Review the campaign again.');
        }
        if (Carbon::parse($draft['saved_at'])->lt(Carbon::now()->subMinutes(15))) {
            $request->session()->forget('smart_notification_draft');

            return redirect()->route('smartNotificationCreate')->with('error', 'The confirmation expired. Review the campaign again.');
        }
        if ((string) $request->input('campaign_uuid') !== (string) $draft['uuid']) {
            return redirect()->route('smartNotificationCreate')->with('error', 'This confirmation does not match the campaign you reviewed.');
        }

        $input = $draft['input'];
        $summary = $notifications->audienceSummary($input);
        if ($summary['eligible'] < 1) {
            return redirect()->route('smartNotificationCreate')->with('error', 'No eligible recipients. The campaign was not created.');
        }

        RateLimiter::hit($key, 60);
        $now = Carbon::now();
        $sendNow = ($input['send_mode'] ?? '') === 'now';
        try {
            $campaignId = DB::transaction(function () use ($draft, $input, $adminId, $now, $sendNow, $summary) {
                $id = DB::table('notification_campaigns')->insertGetId([
                    'campaign_uuid' => $draft['uuid'],
                    'title' => $input['title'],
                    'message' => $input['message'],
                    'category' => $input['category'],
                    'image_url' => $input['image_url'],
                    'deep_link' => $input['deep_link'],
                    'priority' => $input['priority'],
                    'expires_at' => $input['expires_at'],
                    'target_type' => $input['target_type'],
                    'target_role_id' => $input['target_role_id'],
                    'target_user_id' => $input['target_user_id'],
                    'status' => $sendNow ? 'processing' : 'scheduled',
                    'scheduled_at' => $sendNow ? null : $input['scheduled_at'],
                    'total_recipients' => 0,
                    'success_count' => 0,
                    'failure_count' => 0,
                    'skipped_count' => 0,
                    'created_by' => $adminId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('notification_campaign_events')->insert([
                    'campaign_id' => $id,
                    'actor_id' => $adminId,
                    'action' => 'created',
                    'note' => 'eligible '.$summary['eligible'],
                    'created_at' => $now,
                ]);

                return $id;
            });
        } catch (\Throwable $e) {
            $duplicate = str_contains($e->getMessage(), 'notification_campaigns_campaign_uuid_unique');

            return redirect()->route('smartNotificationCreate')->with('error', $duplicate
                ? 'This campaign was already submitted.'
                : 'The campaign could not be saved.');
        }

        $request->session()->forget('smart_notification_draft');
        if (! $sendNow) {
            return redirect()->route('smartNotifications')->with('status', 'Campaign '.$campaignId.' is scheduled. It is not sent from this page. A server scheduler still has to pick it up.');
        }

        $result = $dispatcher->send($campaignId);
        if (! $result['ok']) {
            DB::table('notification_campaigns')->where('id', $campaignId)->where('status', 'processing')->update([
                'status' => 'draft',
                'updated_at' => Carbon::now(),
            ]);

            return redirect()->route('smartNotifications')->with('error', 'Campaign '.$campaignId.' was saved as a draft because sending did not start. '.$result['output']);
        }

        $fresh = DB::table('notification_campaigns')->where('id', $campaignId)->first(['status', 'success_count', 'failure_count']);
        $queue = $this->userQueueDriver();
        $message = 'Campaign '.$campaignId.' finished with status '.($fresh->status ?? 'processing').'.';
        if ($queue === 'sync') {
            $message .= ' The user app queue is sync, so this ran in the request and is not a background worker.';
        } else {
            $message .= ' The user app queue is '.$queue.'. Delivery finishes only after that worker runs.';
        }

        return redirect()->route('smartNotifications')->with('status', $message);
    }

    private function validated(Request $request, SmartNotificationService $notifications): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:1000'],
            'category' => ['required', Rule::in(array_keys(SmartNotificationService::CATEGORIES))],
            'image_url' => ['nullable', 'string', 'max:500'],
            'deep_link' => ['nullable', 'string', 'max:500'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high'])],
            'expires_date' => ['nullable', 'date'],
            'expires_time' => ['nullable', 'date_format:H:i'],
            'audience' => ['required', Rule::in(['all', 'user', 'role:4', 'role:5', 'role:6'])],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'send_mode' => ['required', Rule::in(['now', 'schedule'])],
            'schedule_date' => ['nullable', 'date'],
            'schedule_time' => ['nullable', 'date_format:H:i'],
        ]);

        $title = trim(preg_replace('/\s+/', ' ', strip_tags((string) $data['title'])) ?? '');
        $message = trim(strip_tags((string) $data['message']));
        if ($title === '' || $message === '') {
            $this->failValidation('title', 'Title and message are required.');
        }

        $image = trim((string) ($data['image_url'] ?? ''));
        if ($image !== '' && ! preg_match('#^https://#i', $image)) {
            $this->failValidation('image_url', 'Image URL must start with https://.');
        }
        $linkInput = trim((string) ($data['deep_link'] ?? ''));
        $link = $notifications->safeDeepLink($linkInput);
        if ($linkInput !== '' && $link === null) {
            $this->failValidation('deep_link', 'Deep link must be a netcellpay.in https URL, a netcellpay:// link, or a site path.');
        }

        $expires = $notifications->normalizeSchedule($data['expires_date'] ?? null, $data['expires_time'] ?? null);
        if ((($data['expires_date'] ?? null) || ($data['expires_time'] ?? null)) && $expires === null) {
            $this->failValidation('expires_date', 'Enter both an expiry date and time.');
        }
        if ($expires && $expires->lte(Carbon::now())) {
            $this->failValidation('expires_date', 'Expiry must be in the future.');
        }

        $scheduled = null;
        if ($data['send_mode'] === 'schedule') {
            $scheduled = $notifications->normalizeSchedule($data['schedule_date'] ?? null, $data['schedule_time'] ?? null);
            if ($scheduled === null || $scheduled->lte(Carbon::now())) {
                $this->failValidation('schedule_date', 'Choose a future schedule date and time.');
            }
            if ($expires && $scheduled->gte($expires)) {
                $this->failValidation('schedule_date', 'The schedule must be before the expiry.');
            }
        }

        $targetType = 'all';
        $roleId = null;
        $userId = null;
        if ($data['audience'] === 'user') {
            $targetType = 'user';
            $userId = $notifications->resolveUserIdByMobile((string) ($data['mobile_number'] ?? ''));
            if ($userId === null) {
                $this->failValidation('mobile_number', 'That mobile is not an active retailer, distributor, or other non-admin user.');
            }
        } elseif (str_starts_with($data['audience'], 'role:')) {
            $targetType = 'role';
            $roleId = (int) substr($data['audience'], 5);
        }

        return [
            'title' => $title,
            'message' => $message,
            'category' => $data['category'],
            'image_url' => $image !== '' ? $image : null,
            'deep_link' => $link,
            'priority' => $data['priority'],
            'expires_at' => $expires ? $expires->toDateTimeString() : null,
            'target_type' => $targetType,
            'target_role_id' => $roleId,
            'target_user_id' => $userId,
            'send_mode' => $data['send_mode'],
            'scheduled_at' => $scheduled ? $scheduled->toDateTimeString() : null,
        ];
    }

    private function failValidation(string $field, string $message): void
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    private function userQueueDriver(): string
    {
        $envFile = dirname(base_path()).DIRECTORY_SEPARATOR.'.env';
        if (! is_file($envFile)) {
            return 'unknown';
        }
        $contents = (string) file_get_contents($envFile);
        if (preg_match('/^QUEUE_CONNECTION=(.*)$/m', $contents, $match)) {
            return trim($match[1]) ?: 'sync';
        }

        return 'sync';
    }
}
