<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RetailerNoticeService
{
    public static function alertLowBalance(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }
        self::ensureColumn();
        $threshold = (float) SystemSettingService::get('balance_alert_below', 500);
        if ($threshold <= 0) {
            return;
        }

        $user = DB::table('users')->where('id', $userId)->first();
        if (! $user || in_array((int) ($user->role_id ?? 0), [1, 2], true)) {
            return;
        }
        if (isset($user->deleted_at) && (int) $user->deleted_at === 1) {
            return;
        }

        $today = Carbon::today()->toDateString();
        $balance = (float) $user->wallet_balance;
        if ($balance >= $threshold) {
            if (! empty($user->low_balance_notified_on)) {
                DB::table('users')->where('id', $userId)->update(['low_balance_notified_on' => null]);
            }

            return;
        }
        if ((string) ($user->low_balance_notified_on ?? '') === $today) {
            return;
        }

        $name = trim((string) (($user->outlet_name ?? '') !== '' ? $user->outlet_name : $user->first_name));
        $content = 'NETCELL PAY: '.$name.', your wallet balance is Rs '.number_format($balance, 2, '.', '').', which is below Rs '.number_format($threshold, 2, '.', '').'. Please add money to continue recharge.';
        self::ensureTemplate('low_balance', $content);
        $tmp = self::smsTemplate('low_balance');
        if (! \helpers::whatsappEnabled('low_balance', $tmp)) {
            return;
        }
        $marked = DB::table('users')
            ->where('id', $userId)
            ->where(function ($q) use ($today) {
                $q->whereNull('low_balance_notified_on')
                    ->orWhere('low_balance_notified_on', '<', $today);
            })
            ->update(['low_balance_notified_on' => $today]);
        if (! $marked) {
            return;
        }
        \helpers::sendQueuedWhatsapp('low_balance', $user->id, (string) $user->mobile_number, $content, $tmp);
    }

    public static function scanLowBalances(): int
    {
        self::ensureColumn();
        $threshold = (float) SystemSettingService::get('balance_alert_below', 500);
        if ($threshold <= 0) {
            return 0;
        }
        $today = Carbon::today()->toDateString();
        $q = DB::table('users')
            ->where('status', 1)
            ->where('wallet_balance', '<', $threshold)
            ->whereNotIn('role_id', [1, 2])
            ->where(function ($w) use ($today) {
                $w->whereNull('low_balance_notified_on')
                    ->orWhere('low_balance_notified_on', '<', $today);
            });
        if (Schema::hasColumn('users', 'deleted_at')) {
            $q->where(function ($w) {
                $w->where('deleted_at', 0)->orWhereNull('deleted_at');
            });
        }
        $ids = $q->orderBy('id')->limit(25)->pluck('id');
        foreach ($ids as $id) {
            try {
                self::alertLowBalance((int) $id);
            } catch (\Throwable $e) {
            }
        }

        return $ids->count();
    }

    public static function sendDailySales(): bool
    {
        if (Carbon::now()->hour < 7) {
            return false;
        }
        if (Schema::hasTable('messages')) {
            $sent = DB::table('messages')
                ->where('subject', 'daily_sales')
                ->whereDate('created_at', Carbon::today()->toDateString())
                ->exists();
            if ($sent) {
                return false;
            }
        }

        $day = Carbon::yesterday();
        $from = $day->copy()->startOfDay();
        $to = $day->copy()->endOfDay();
        $sum = function (array $types, array $statuses) use ($from, $to): array {
            $row = DB::table('reports')
                ->whereIn('transaction_type', $types)
                ->whereBetween('created_at', [$from, $to]);
            if ($statuses !== []) {
                $row->whereIn('status', $statuses);
            }
            $data = $row->selectRaw('COUNT(*) as total, COALESCE(SUM(total_amount), 0) as amount')->first();

            return ['count' => (int) ($data->total ?? 0), 'amount' => (float) ($data->amount ?? 0)];
        };

        $success = $sum(['Recharge', 'Bill Pay'], ['Success']);
        $failed = $sum(['Recharge', 'Bill Pay'], ['Failed', 'Failure']);
        $pending = $sum(['Recharge', 'Bill Pay'], ['Pending', 'Under Process', 'Under Proces', 'Processing']);
        $refund = $sum(['Refund'], ['Success']);

        $content = 'NETCELL PAY sales for '.$day->format('d-m-Y')
            .'. Success Rs '.number_format($success['amount'], 2, '.', '').' ('.$success['count'].')'
            .'. Failed Rs '.number_format($failed['amount'], 2, '.', '').' ('.$failed['count'].')'
            .'. Refund Rs '.number_format($refund['amount'], 2, '.', '').' ('.$refund['count'].')'
            .'. Pending Rs '.number_format($pending['amount'], 2, '.', '').' ('.$pending['count'].').';

        self::ensureTemplate('daily_sales', $content);
        $tmp = self::smsTemplate('daily_sales');
        if (! \helpers::whatsappEnabled('daily_sales', $tmp)) {
            return false;
        }
        $mobiles = self::adminMobiles();
        if ($mobiles === []) {
            return false;
        }
        foreach ($mobiles as $admin) {
            \helpers::sendQueuedWhatsapp('daily_sales', $admin['id'], $admin['mobile'], $content, $tmp);
        }

        return true;
    }

    private static function adminMobiles(): array
    {
        $out = [];
        $admins = DB::table('users')->where('role_id', 1)->where('status', 1)->limit(3)->get(['id', 'mobile_number']);
        foreach ($admins as $admin) {
            $mobile = self::indianMobile((string) $admin->mobile_number);
            if ($mobile !== '') {
                $out[] = ['id' => (int) $admin->id, 'mobile' => $mobile];
            }
        }
        if ($out !== []) {
            return $out;
        }
        $company = DB::table('companies')->orderBy('id')->first(['support_number']);
        $mobile = self::indianMobile((string) ($company->support_number ?? ''));
        if ($mobile !== '') {
            $out[] = ['id' => 1, 'mobile' => $mobile];
        }

        return $out;
    }

    private static function indianMobile(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return preg_match('/^[6-9][0-9]{9}$/', $digits) ? $digits : '';
    }

    private static function smsTemplate(string $slug): ?object
    {
        try {
            if (! Schema::hasTable('sms_templates')) {
                return null;
            }

            return DB::table('sms_templates')->where('slug', $slug)->first(['template_id', 'content', 'status']);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function ensureColumn(): void
    {
        try {
            if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'low_balance_notified_on')) {
                DB::statement('ALTER TABLE `users` ADD COLUMN `low_balance_notified_on` DATE NULL');
            }
        } catch (\Throwable $e) {
        }
    }

    private static function ensureTemplate(string $slug, string $content): void
    {
        try {
            if (! Schema::hasTable('whatsapp_templates')) {
                return;
            }
            $exists = DB::table('whatsapp_templates')->where('slug', $slug)->exists();
            if ($exists) {
                return;
            }
            $row = [
                'slug' => $slug,
                'template_id' => '',
                'content' => $content,
                'status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            $columns = array_flip(Schema::getColumnListing('whatsapp_templates'));
            DB::table('whatsapp_templates')->insert(array_intersect_key($row, $columns));
        } catch (\Throwable $e) {
        }
    }
}
