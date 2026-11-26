<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SmartNotificationService
{
    public const CATEGORIES = [
        'general' => 'General',
        'wallet' => 'Wallet',
        'recharge' => 'Recharge',
        'refund' => 'Refund',
        'security' => 'Security',
        'offers' => 'Offers',
        'announcements' => 'Announcements',
        'maintenance' => 'Maintenance',
    ];

    public const TRANSACTIONAL = ['wallet', 'recharge', 'refund', 'security', 'maintenance'];

    public function statistics(): array
    {
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

    public function recentCampaigns(int $limit = 8)
    {
        return DB::table('notification_campaigns')
            ->orderByDesc('id')
            ->limit($limit)
            ->get([
                'id', 'title', 'category', 'target_type', 'target_role_id', 'status',
                'total_recipients', 'success_count', 'failure_count', 'scheduled_at', 'created_at',
            ]);
    }

    public function requiresConsent(string $category): bool
    {
        return ! in_array(strtolower(trim($category)), self::TRANSACTIONAL, true);
    }

    public function eligibleQuery(array $input): Builder
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

        if ($this->requiresConsent((string) ($input['category'] ?? ''))) {
            $query->where('promotional_push_consent', 1);
        }

        return $query;
    }

    public function audienceSummary(array $input): array
    {
        $eligible = $this->eligibleQuery($input);
        $withToken = (clone $eligible)->where(function ($inner) {
            $inner->where(function ($token) {
                $token->whereNotNull('android_fcm_token')->where('android_fcm_token', '!=', '');
            })->orWhere(function ($token) {
                $token->whereNotNull('fcm_token')->where('fcm_token', '!=', '');
            })->orWhere(function ($token) {
                $token->whereNotNull('device_token')->where('device_token', '!=', '');
            });
        });

        return [
            'eligible' => (int) $eligible->count(),
            'with_token' => (int) $withToken->count(),
        ];
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
