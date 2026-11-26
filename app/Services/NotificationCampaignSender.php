<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationCampaignSender
{
    /** @var array<int, int>|null Test seam. Production leaves this null. */
    public static ?array $onlyUserIds = null;

    public const PROMOTIONAL = ['general', 'offers', 'announcements'];

    public const TRANSACTIONAL = ['wallet', 'recharge', 'refund', 'security', 'maintenance'];

    public function deliver(int $campaignId, bool $retryFailed = false, int $attempt = 1, int $maxAttempts = 3): void
    {
        $campaign = $this->begin($campaignId, $retryFailed);
        if ($campaign === null) {
            return;
        }

        $eligible = $this->recipientQuery($campaign)->count();
        DB::table('notification_campaigns')->where('id', $campaignId)->update([
            'total_recipients' => $eligible,
            'updated_at' => Carbon::now(),
        ]);

        $needsRetry = false;
        $this->recipientQuery($campaign)->chunkById(50, function ($users) use ($campaign, $retryFailed, $attempt, $maxAttempts, &$needsRetry) {
            if ($this->mustStop($campaign->id)) {
                return false;
            }
            foreach ($users as $user) {
                $result = $this->deliverOne($campaign, $user, $retryFailed, $attempt, $maxAttempts);
                if ($result === 'retry') {
                    $needsRetry = true;
                }
            }
            $this->refreshCounters($campaign->id);

            return true;
        });

        $this->refreshCounters($campaignId);
        if ($this->mustStop($campaignId)) {
            $this->finalize($campaignId, $this->stopReason($campaignId));

            return;
        }
        if ($needsRetry && $attempt < $maxAttempts) {
            throw new \RuntimeException('Campaign delivery will retry remaining recipients.');
        }
        $this->finalize($campaignId, null);
    }

    public function markInterrupted(int $campaignId): void
    {
        $campaign = DB::table('notification_campaigns')->where('id', $campaignId)->first();
        if (! $campaign || in_array($campaign->status, ['cancelled', 'completed', 'failed', 'partially_failed'], true)) {
            return;
        }
        $this->refreshCounters($campaignId);
        $pending = $this->pendingCount($campaignId);
        if ($pending > 0 || (int) $campaign->success_count === 0) {
            $status = (int) DB::table('notification_campaigns')->where('id', $campaignId)->value('success_count') > 0
                ? 'partially_failed'
                : 'failed';
            DB::table('notification_campaigns')->where('id', $campaignId)->where('status', 'processing')->update([
                'status' => $status,
                'completed_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            $this->event($campaignId, (int) $campaign->created_by, 'completed', $status);
        }
    }

    public function acquireRecipientLock(int $campaignId, int $userId): bool
    {
        $row = DB::selectOne('SELECT GET_LOCK(?, 2) AS got', [$this->lockName($campaignId, $userId)]);

        return (int) ($row->got ?? 0) === 1;
    }

    public function releaseRecipientLock(int $campaignId, int $userId): void
    {
        DB::selectOne('SELECT RELEASE_LOCK(?) AS got', [$this->lockName($campaignId, $userId)]);
    }

    private function begin(int $campaignId, bool $retryFailed): ?object
    {
        return DB::transaction(function () use ($campaignId, $retryFailed) {
            $campaign = DB::table('notification_campaigns')->where('id', $campaignId)->lockForUpdate()->first();
            if (! $campaign) {
                return null;
            }
            if ($campaign->status === 'cancelled' || $campaign->status === 'completed' || $campaign->status === 'draft') {
                return null;
            }
            if ($this->isExpired($campaign)) {
                $this->finalize($campaignId, 'expired');

                return null;
            }
            if (in_array($campaign->status, ['failed', 'partially_failed'], true) && ! $retryFailed) {
                return null;
            }
            if ($campaign->status === 'scheduled') {
                if ($campaign->scheduled_at && Carbon::parse($campaign->scheduled_at)->gt(Carbon::now())) {
                    return null;
                }
            }

            $updates = ['updated_at' => Carbon::now()];
            if ($campaign->started_at === null) {
                $updates['started_at'] = Carbon::now();
            }
            if ($campaign->status !== 'processing') {
                $updates['status'] = 'processing';
            }
            DB::table('notification_campaigns')->where('id', $campaignId)->update($updates);
            $this->event($campaignId, (int) $campaign->created_by, 'confirmed', $retryFailed ? 'retry' : 'processing');
            if ($retryFailed) {
                $this->event($campaignId, (int) $campaign->created_by, 'retry', 'failed recipients');
            }

            return DB::table('notification_campaigns')->where('id', $campaignId)->first();
        });
    }

    private function deliverOne(object $campaign, object $user, bool $retryFailed, int $attempt, int $maxAttempts): string
    {
        $userId = (int) $user->id;
        if (! $this->acquireRecipientLock((int) $campaign->id, $userId)) {
            return 'retry';
        }

        try {
            $row = $this->deliveryRow($campaign, $userId);
            $status = (int) $row->status;
            if ($status === 1) {
                return 'skip';
            }
            if ($status === 2 && ! $retryFailed) {
                return 'skip';
            }
            if ($status === 3 && ! $retryFailed) {
                return 'skip';
            }

            $token = $this->tokenFromUser($user);
            if ($token === null) {
                $this->saveDelivery((int) $row->id, 2, 'no_token');

                return 'skip';
            }

            $data = $this->payloadData($campaign);
            $title = $this->plain((string) $campaign->title, 120);
            $body = $this->plain((string) $campaign->message, 1000);
            $ok = FcmHttpV1Service::send($token, $title, $body, $data, $userId);
            $error = $this->safeError(FcmHttpV1Service::lastError(), $token);
            if ($ok) {
                $this->saveDelivery((int) $row->id, 1, null);

                return 'sent';
            }
            if ($this->isTransient($error) && $attempt < $maxAttempts) {
                $this->saveDelivery((int) $row->id, 0, $error !== '' ? $error : 'temporary failure');

                return 'retry';
            }
            $this->saveDelivery((int) $row->id, 3, $error !== '' ? $error : 'delivery failed');

            return 'failed';
        } finally {
            $this->releaseRecipientLock((int) $campaign->id, $userId);
        }
    }

    private function deliveryRow(object $campaign, int $userId): object
    {
        $existing = DB::table('messages')
            ->where('campaign_id', $campaign->id)
            ->where('to_user_id', $userId)
            ->first();
        if ($existing) {
            return $existing;
        }

        $now = Carbon::now();
        try {
            $id = DB::table('messages')->insertGetId([
                'user_id' => (int) $campaign->created_by,
                'to_user_id' => $userId,
                'subject' => $this->plain((string) $campaign->title, 191),
                'msg_source' => 'PUSH',
                'template_id' => '0',
                'content' => $this->plain((string) $campaign->message, 5000),
                'status' => 0,
                'campaign_id' => (int) $campaign->id,
                'delivery_error' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (QueryException $e) {
            if (! $this->isDuplicate($e)) {
                throw $e;
            }
            $existing = DB::table('messages')
                ->where('campaign_id', $campaign->id)
                ->where('to_user_id', $userId)
                ->first();
            if ($existing) {
                return $existing;
            }
            throw $e;
        }

        return DB::table('messages')->where('id', $id)->first();
    }

    private function recipientQuery(object $campaign)
    {
        $query = DB::table('users')
            ->select(['id', 'role_id', 'android_fcm_token', 'fcm_token', 'device_token'])
            ->where('status', 1)
            ->where(function ($inner) {
                $inner->where('deleted_at', 0)->orWhereNull('deleted_at');
            })
            ->whereNotIn('role_id', [1, 2]);

        $target = strtolower(trim((string) $campaign->target_type));
        if ($target === 'user') {
            $query->where('id', (int) $campaign->target_user_id);
        } elseif ($target === 'role') {
            $roleId = (int) $campaign->target_role_id;
            if (! in_array($roleId, [3, 4, 5, 6], true)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('role_id', $roleId);
            }
        } elseif ($target !== 'all') {
            $query->whereRaw('1 = 0');
        }

        if ($this->requiresConsent((string) $campaign->category)) {
            $query->where('promotional_push_consent', 1);
        }
        if (self::$onlyUserIds !== null) {
            $query->whereIn('id', self::$onlyUserIds);
        }

        return $query;
    }

    private function requiresConsent(string $category): bool
    {
        return ! in_array(strtolower(trim($category)), self::TRANSACTIONAL, true);
    }

    private function payloadData(object $campaign): array
    {
        $data = [
            'category' => (string) $campaign->category,
            'campaign_id' => (string) $campaign->id,
            'android_priority' => strtolower((string) $campaign->priority) === 'high' ? 'HIGH' : 'NORMAL',
        ];
        $image = trim((string) ($campaign->image_url ?? ''));
        if (preg_match('#^https://#i', $image)) {
            $data['image'] = $image;
        }
        $link = $this->safeDeepLink($campaign->deep_link ?? null);
        if ($link !== null) {
            $data['deep_link'] = $link;
        }
        if (! empty($campaign->expires_at)) {
            $seconds = Carbon::parse($campaign->expires_at)->getTimestamp() - Carbon::now()->getTimestamp();
            if ($seconds > 0) {
                $data['android_ttl'] = (string) min($seconds, 2419200);
            }
        }

        return $data;
    }

    public function safeDeepLink(?string $link): ?string
    {
        $link = trim((string) $link);
        if ($link === '') {
            return null;
        }
        if (Str::startsWith($link, '/') && ! Str::startsWith($link, '//')) {
            return $link;
        }
        if (Str::startsWith(strtolower($link), 'netcellpay://')) {
            return $link;
        }
        $parts = parse_url($link);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($scheme === 'https' && ($host === 'netcellpay.in' || Str::endsWith($host, '.netcellpay.in'))) {
            return $link;
        }

        return null;
    }

    private function tokenFromUser(object $user): ?string
    {
        foreach (['android_fcm_token', 'fcm_token', 'device_token'] as $column) {
            $token = trim((string) ($user->{$column} ?? ''));
            if ($token !== '') {
                return $token;
            }
        }

        return null;
    }

    private function saveDelivery(int $messageId, int $status, ?string $error): void
    {
        DB::table('messages')->where('id', $messageId)->update([
            'status' => $status,
            'delivery_error' => $error,
            'updated_at' => Carbon::now(),
        ]);
    }

    private function refreshCounters(int $campaignId): void
    {
        $counts = DB::table('messages')
            ->where('campaign_id', $campaignId)
            ->selectRaw('COUNT(*) as total_rows')
            ->selectRaw('SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as success_count')
            ->selectRaw('SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as failure_count')
            ->selectRaw('SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as skipped_count')
            ->first();
        DB::table('notification_campaigns')->where('id', $campaignId)->update([
            'success_count' => (int) ($counts->success_count ?? 0),
            'failure_count' => (int) ($counts->failure_count ?? 0),
            'skipped_count' => (int) ($counts->skipped_count ?? 0),
            'updated_at' => Carbon::now(),
        ]);
    }

    private function finalize(int $campaignId, ?string $reason): void
    {
        $campaign = DB::table('notification_campaigns')->where('id', $campaignId)->first();
        if (! $campaign) {
            return;
        }
        $this->refreshCounters($campaignId);
        $campaign = DB::table('notification_campaigns')->where('id', $campaignId)->first();
        if ($campaign->status === 'cancelled') {
            return;
        }
        if ($this->pendingCount($campaignId) > 0 && $reason === null) {
            DB::table('notification_campaigns')->where('id', $campaignId)->update([
                'status' => 'processing',
                'updated_at' => Carbon::now(),
            ]);

            return;
        }

        $success = (int) $campaign->success_count;
        $failure = (int) $campaign->failure_count;
        $total = (int) $campaign->total_recipients;
        if ($success > 0 && ($failure > 0 || ($reason !== null && $success < $total))) {
            $status = 'partially_failed';
        } elseif ($success === 0 && ($total > 0 || $reason !== null)) {
            $status = 'failed';
        } else {
            $status = 'completed';
        }
        DB::table('notification_campaigns')->where('id', $campaignId)->where('status', '<>', 'cancelled')->update([
            'status' => $status,
            'completed_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        $note = $reason !== null ? $status.' '.$reason : $status;
        $this->event($campaignId, (int) $campaign->created_by, 'completed', mb_substr($note, 0, 255));
    }

    private function pendingCount(int $campaignId): int
    {
        return (int) DB::table('messages')->where('campaign_id', $campaignId)->where('status', 0)->count();
    }

    private function mustStop(int $campaignId): bool
    {
        $campaign = DB::table('notification_campaigns')->where('id', $campaignId)->first(['status', 'expires_at']);

        return ! $campaign || $campaign->status === 'cancelled' || $this->isExpired($campaign);
    }

    private function stopReason(int $campaignId): string
    {
        $campaign = DB::table('notification_campaigns')->where('id', $campaignId)->first(['status', 'expires_at']);
        if ($campaign && $campaign->status === 'cancelled') {
            return 'cancelled';
        }

        return 'expired';
    }

    private function isExpired(object $campaign): bool
    {
        if (empty($campaign->expires_at)) {
            return false;
        }

        return Carbon::parse($campaign->expires_at)->lte(Carbon::now());
    }

    private function event(int $campaignId, int $actorId, string $action, string $note): void
    {
        if ($action === 'confirmed' && DB::table('notification_campaign_events')->where('campaign_id', $campaignId)->where('action', 'confirmed')->exists()) {
            return;
        }
        if ($action === 'completed' && DB::table('notification_campaign_events')->where('campaign_id', $campaignId)->where('action', 'completed')->exists()) {
            return;
        }
        DB::table('notification_campaign_events')->insert([
            'campaign_id' => $campaignId,
            'actor_id' => $actorId > 0 ? $actorId : 0,
            'action' => $action,
            'note' => mb_substr($note, 0, 255),
            'created_at' => Carbon::now(),
        ]);
    }

    private function isTransient(string $error): bool
    {
        $hay = strtoupper($error);
        foreach (['UNREGISTERED', 'NOT_FOUND', 'INVALID_ARGUMENT', 'SENDER_ID_MISMATCH'] as $permanent) {
            if (str_contains($hay, $permanent)) {
                return false;
            }
        }
        if (preg_match('/HTTP\s+(0|408|429|500|502|503|504)\b/', $hay)) {
            return true;
        }

        return str_contains($hay, 'OAUTH') || str_contains($hay, 'TIMEOUT') || str_contains($hay, 'TIMED OUT');
    }

    private function safeError(?string $error, string $token): string
    {
        $error = trim((string) $error);
        if ($token !== '') {
            $error = str_replace($token, '[token]', $error);
        }
        $error = preg_replace('/-----BEGIN PRIVATE KEY-----.*?-----END PRIVATE KEY-----/s', '[redacted]', $error) ?? $error;
        $error = preg_replace('/Bearer\s+[A-Za-z0-9._\-]+/', 'Bearer [redacted]', $error) ?? $error;

        return mb_substr($error, 0, 255);
    }

    private function plain(string $value, int $limit): string
    {
        $value = trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?? '');

        return mb_substr($value, 0, $limit);
    }

    private function isDuplicate(QueryException $exception): bool
    {
        $state = (string) ($exception->errorInfo[0] ?? '');

        return $state === '23000' || str_contains($exception->getMessage(), 'messages_campaign_recipient_unique');
    }

    private function lockName(int $campaignId, int $userId): string
    {
        return mb_substr('nc'.$campaignId.'_'.$userId, 0, 64);
    }
}
