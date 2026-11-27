<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SmartNotificationService
{
    public const CATEGORIES = [
        'general' => 'General',
        'offers' => 'Offers',
        'announcements' => 'Announcements',
    ];

    public const LEGACY_CATEGORIES = [
        'wallet' => 'Wallet',
        'recharge' => 'Recharge',
        'refund' => 'Refund',
        'security' => 'Security',
        'maintenance' => 'Maintenance',
    ];

    public function schemaReady(): bool
    {
        return Schema::hasTable('notification_campaigns')
            && Schema::hasTable('notification_campaign_events')
            && Schema::hasTable('messages')
            && Schema::hasColumn('messages', 'campaign_id')
            && Schema::hasColumn('users', 'promotional_push_consent');
    }

    public function statistics(): array
    {
        if (! $this->schemaReady()) {
            return $this->emptyStatistics();
        }

        $campaigns = DB::table('notification_campaigns')
            ->selectRaw('COUNT(*) as total_campaigns')
            ->selectRaw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_campaigns")
            ->selectRaw("SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_campaigns")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_campaigns")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_campaigns")
            ->selectRaw("SUM(CASE WHEN status = 'partially_failed' THEN 1 ELSE 0 END) as partially_failed_campaigns")
            ->selectRaw("SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_campaigns")
            ->selectRaw('COALESCE(SUM(total_recipients), 0) as total_recipients')
            ->first();

        $deliveries = DB::table('messages')
            ->whereNotNull('campaign_id')
            ->selectRaw('SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as successful_deliveries')
            ->selectRaw('SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as failed_deliveries')
            ->first();

        return [
            'total_campaigns' => (int) ($campaigns->total_campaigns ?? 0),
            'draft_campaigns' => (int) ($campaigns->draft_campaigns ?? 0),
            'processing_campaigns' => (int) ($campaigns->processing_campaigns ?? 0),
            'completed_campaigns' => (int) ($campaigns->completed_campaigns ?? 0),
            'failed_campaigns' => (int) ($campaigns->failed_campaigns ?? 0),
            'partially_failed_campaigns' => (int) ($campaigns->partially_failed_campaigns ?? 0),
            'scheduled_campaigns' => (int) ($campaigns->scheduled_campaigns ?? 0),
            'total_recipients' => (int) ($campaigns->total_recipients ?? 0),
            'successful_deliveries' => (int) ($deliveries->successful_deliveries ?? 0),
            'failed_deliveries' => (int) ($deliveries->failed_deliveries ?? 0),
        ];
    }

    public function campaignPage(array $filters): LengthAwarePaginator
    {
        if (! $this->schemaReady()) {
            return new Paginator([], 0, 20);
        }

        $query = DB::table('notification_campaigns');
        $status = (string) ($filters['status'] ?? '');
        if (in_array($status, ['draft', 'scheduled', 'processing', 'completed', 'partially_failed', 'failed', 'cancelled'], true)) {
            $query->where('status', $status);
        }
        $category = (string) ($filters['category'] ?? '');
        if (isset(self::CATEGORIES[$category]) || isset(self::LEGACY_CATEGORIES[$category])) {
            $query->where('category', $category);
        }
        $target = (string) ($filters['target_type'] ?? '');
        if (in_array($target, ['all', 'user', 'role'], true)) {
            $query->where('target_type', $target);
        }
        $from = (string) ($filters['from'] ?? '');
        $to = (string) ($filters['to'] ?? '');
        if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $query->where('created_at', '>=', $from.' 00:00:00');
        }
        if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $query->where('created_at', '<=', $to.' 23:59:59');
        }
        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $query->where('title', 'like', '%'.addcslashes($search, '%_\\').'%');
        }

        return $query->orderByDesc('id')->paginate(20)->withQueryString();
    }

    public function campaign(int $id): ?object
    {
        if (! $this->schemaReady()) {
            return null;
        }

        return DB::table('notification_campaigns as c')
            ->leftJoin('users as creator', 'creator.id', '=', 'c.created_by')
            ->where('c.id', $id)
            ->first([
                'c.*',
                'creator.first_name as created_by_name',
                'creator.mobile_number as created_by_mobile',
            ]);
    }

    public function recipientPage(int $campaignId): LengthAwarePaginator
    {
        return DB::table('messages')
            ->leftJoin('users', 'users.id', '=', 'messages.to_user_id')
            ->where('messages.campaign_id', $campaignId)
            ->orderBy('messages.id')
            ->paginate(50, [
                'messages.id',
                'messages.to_user_id',
                'messages.status',
                'messages.delivery_error',
                'messages.updated_at',
                'users.first_name',
                'users.mobile_number',
                'users.role_id',
            ])
            ->withQueryString();
    }

    private function emptyStatistics(): array
    {
        return [
            'total_campaigns' => 0,
            'draft_campaigns' => 0,
            'processing_campaigns' => 0,
            'completed_campaigns' => 0,
            'failed_campaigns' => 0,
            'partially_failed_campaigns' => 0,
            'scheduled_campaigns' => 0,
            'total_recipients' => 0,
            'successful_deliveries' => 0,
            'failed_deliveries' => 0,
        ];
    }

    public function requiresConsent(string $category): bool
    {
        unset($category);

        return true;
    }

    public function eligibleQuery(array $input, bool $requireConsent = true): Builder
    {
        $query = DB::table('users')
            ->where('status', 1)
            ->where(function ($inner) {
                $inner->where('deleted_at', 0)->orWhereNull('deleted_at');
            })
            ->whereNotIn('role_id', [1, 2]);

        $target = (string) ($input['target_type'] ?? '');
        if ($target === 'user') {
            $query->where('id', (int) ($input['target_user_id'] ?? 0));
        } elseif ($target === 'role') {
            $roleId = (int) ($input['target_role_id'] ?? 0);
            if (! in_array($roleId, [4, 5, 6], true)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('role_id', $roleId);
            }
        } elseif ($target !== 'all') {
            $query->whereRaw('1 = 0');
        }

        if ($requireConsent) {
            $query->where('promotional_push_consent', 1);
        }

        return $query;
    }

    public function audienceSummary(array $input): array
    {
        $audience = $this->eligibleQuery($input, false);
        $approved = (clone $audience)->where('promotional_push_consent', 1);
        $withToken = $this->withToken(clone $approved);
        $total = (int) (clone $audience)->count();
        $consentApproved = (int) (clone $approved)->count();
        $tokenCount = (int) $withToken->count();

        return [
            'total_users' => $total,
            'consent_approved' => $consentApproved,
            'consent_not_approved' => max(0, $total - $consentApproved),
            'no_token' => max(0, $consentApproved - $tokenCount),
            'with_token' => $tokenCount,
            'eligible' => $consentApproved,
            'final_recipients' => $tokenCount,
        ];
    }

    public function deliveryBreakdown(int $campaignId): array
    {
        $row = DB::table('messages')
            ->where('campaign_id', $campaignId)
            ->selectRaw('SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as successful')
            ->selectRaw('SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as no_token')
            ->selectRaw('SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as failed')
            ->selectRaw('SUM(CASE WHEN status = 4 THEN 1 ELSE 0 END) as consent_skipped')
            ->first();

        return [
            'successful' => (int) ($row->successful ?? 0),
            'no_token' => (int) ($row->no_token ?? 0),
            'failed' => (int) ($row->failed ?? 0),
            'consent_skipped' => (int) ($row->consent_skipped ?? 0),
        ];
    }

    private function withToken(Builder $query): Builder
    {
        return $query->where(function ($inner) {
            $inner->where(function ($token) {
                $token->whereNotNull('android_fcm_token')->where('android_fcm_token', '!=', '');
            })->orWhere(function ($token) {
                $token->whereNotNull('fcm_token')->where('fcm_token', '!=', '');
            })->orWhere(function ($token) {
                $token->whereNotNull('device_token')->where('device_token', '!=', '');
            });
        });
    }

    public function resolveUserIdByMobile(string $mobile): ?int
    {
        $mobile = preg_replace('/\D+/', '', $mobile) ?? '';
        if ($mobile === '') {
            return null;
        }
        $id = DB::table('users')
            ->where('mobile_number', $mobile)
            ->where('status', 1)
            ->where(function ($inner) {
                $inner->where('deleted_at', 0)->orWhereNull('deleted_at');
            })
            ->whereNotIn('role_id', [1, 2])
            ->value('id');

        return $id ? (int) $id : null;
    }

    public function userLabel(int $userId): string
    {
        $user = DB::table('users')->where('id', $userId)->first(['id', 'first_name', 'mobile_number', 'role_id']);
        if (! $user) {
            return 'User '.$userId;
        }

        return trim((string) $user->first_name).' · '.(string) $user->mobile_number;
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

    public function audienceLabel(array $input): string
    {
        $target = (string) ($input['target_type'] ?? '');
        if ($target === 'all') {
            return 'All eligible users';
        }
        if ($target === 'user') {
            return 'Selected user';
        }
        $roles = [4 => 'Master Distributor', 5 => 'Distributor', 6 => 'Retailer'];

        return $roles[(int) ($input['target_role_id'] ?? 0)] ?? 'Role';
    }

    public function normalizeSchedule(?string $date, ?string $time): ?Carbon
    {
        $date = trim((string) $date);
        $time = trim((string) $time);
        if ($date === '' || $time === '') {
            return null;
        }

        return Carbon::parse($date.' '.$time, config('app.timezone'));
    }
}
