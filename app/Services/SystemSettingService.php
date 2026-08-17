<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemSettingService
{
    public static function defaults(): array
    {
        return [
            'fund_interval_minute' => '15',
            'min_fund_transfer' => '500',
            'balance_alert_below' => '500',
            'wrong_login_attempt' => '3',
            'interval_recharge_minute' => '30',
            'max_fund_transfer' => '50000',
            'referral_amount' => '0',
            'max_payout_account' => '20',
            'stop_all_transactions' => '0',
            'app_without_login' => '0',
            'activation_charge' => '0',
            'activation_charge_status' => '0',
            'add_money_charge_type' => 'fixed',
            'add_money_charge_value' => '0',
            'add_money_charge_status' => '0',
            'min_add_money' => '100',
            'min_signup_amount' => '0',
            'pusher_app_id' => '',
            'pusher_key' => '',
            'pusher_secret' => '',
            'pusher_cluster' => 'ap2',
        ];
    }

    public static function ensureTable(): void
    {
        try {
            if (Schema::hasTable('system_settings')) {
                return;
            }
        } catch (\Throwable $e) {
        }

        try {
            DB::statement("CREATE TABLE IF NOT EXISTS `system_settings` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `setting_key` VARCHAR(80) NOT NULL,
                `setting_value` TEXT NULL,
                `created_at` TIMESTAMP NULL DEFAULT NULL,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `system_settings_setting_key_unique` (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {
            \Log::warning('system_settings create failed: '.$e->getMessage());
        }
    }

    public static function all(): array
    {
        try {
            self::ensureTable();
            $rows = DB::table('system_settings')->pluck('setting_value', 'setting_key')->toArray();
            if (!is_array($rows)) {
                $rows = [];
            }

            return array_merge(self::defaults(), $rows);
        } catch (\Throwable $e) {
            \Log::warning('system_settings read failed: '.$e->getMessage());
            return self::defaults();
        }
    }

    public static function get(string $key, $default = null)
    {
        $all = self::all();
        if (array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '') {
            return $all[$key];
        }

        return $default ?? (self::defaults()[$key] ?? null);
    }

    public static function isOn(string $key): bool
    {
        try {
            return (string) self::get($key, '0') === '1';
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function blockedMessage(): ?string
    {
        if (self::isOn('stop_all_transactions')) {
            return 'All transactions are temporarily stopped. Please try later.';
        }

        return null;
    }

    public static function rechargeRepeatMessage($userId, $number, $amount, $providerId): ?string
    {
        $minutes = max(0, (int) self::get('interval_recharge_minute', 30));
        if ($minutes <= 0) {
            return null;
        }

        $exists = DB::table('reports')
            ->where('user_id', $userId)
            ->where('number', $number)
            ->where('total_amount', $amount)
            ->where('provider_id', $providerId)
            ->where('created_at', '>=', Carbon::now()->subMinutes($minutes))
            ->exists();

        if ($exists) {
            return 'Same recharge was done recently. Please wait '.$minutes.' minutes.';
        }

        return null;
    }

    public static function serviceDisabledMessage($provider): ?string
    {
        if (!$provider || empty($provider->service_id)) {
            return null;
        }

        $service = DB::table('services')->where('id', $provider->service_id)->first();
        if ($service && (int) $service->status !== 1) {
            return ($service->service_name ?? 'This service').' is currently deactivated.';
        }

        return null;
    }

    public static function fundTransferMessage($user, $amount): ?string
    {
        $stopped = self::blockedMessage();
        if ($stopped) {
            return $stopped;
        }

        $amount = (float) $amount;
        $min = (float) self::get('min_fund_transfer', 500);
        $max = (float) self::get('max_fund_transfer', 50000);
        if ($min > 0 && $amount < $min) {
            return 'Minimum fund transfer amount is '.$min;
        }
        if ($max > 0 && $amount > $max) {
            return 'Maximum fund transfer amount is '.$max;
        }

        $minutes = max(0, (int) self::get('fund_interval_minute', 15));
        if ($minutes > 0 && $user && !empty($user->id)) {
            $recent = DB::table('reports')
                ->where('user_id', $user->id)
                ->where('transaction_type', 'Transfer Money')
                ->where('status', 'Success')
                ->where('created_at', '>=', Carbon::now()->subMinutes($minutes))
                ->exists();
            if ($recent) {
                return 'Please wait '.$minutes.' minutes between fund transfers.';
            }
        }

        return null;
    }

    public static function failedLoginMessage($user): ?string
    {
        if (!$user) {
            return null;
        }

        $max = max(1, (int) self::get('wrong_login_attempt', 3));
        $key = 'login_fail_'.$user->id;
        $attempts = (int) Cache::get($key, 0);
        if ($attempts >= $max) {
            return 'Too many wrong login attempts. Try again after 15 minutes.';
        }

        return null;
    }

    public static function recordFailedLogin($user): string
    {
        if (!$user) {
            return 'password do not match';
        }

        $max = max(1, (int) self::get('wrong_login_attempt', 3));
        $key = 'login_fail_'.$user->id;
        $attempts = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, now()->addMinutes(15));
        $left = max(0, $max - $attempts);
        if ($left <= 0) {
            return 'Too many wrong login attempts. Try again after 15 minutes.';
        }

        return 'password do not match. '.$left.' attempt(s) left.';
    }

    public static function clearFailedLogin($user): void
    {
        if ($user && !empty($user->id)) {
            Cache::forget('login_fail_'.$user->id);
        }
    }

    public static function payoutLimitMessage(int $userId): ?string
    {
        $max = max(0, (int) self::get('max_payout_account', 20));
        if ($max <= 0) {
            return null;
        }

        $count = DB::table('banks')
            ->where('user_id', $userId)
            ->where('deleted_at', '!=', 1)
            ->count();
        if ($count >= $max) {
            return 'Maximum payout accounts allowed is '.$max;
        }

        return null;
    }

    public static function creditReferral(int $parentId, int $newUserId): void
    {
        $amount = (float) self::get('referral_amount', 0);
        if ($amount <= 0 || $parentId <= 0 || $newUserId <= 0) {
            return;
        }

        $parent = DB::table('users')->where('id', $parentId)->first();
        $child = DB::table('users')->where('id', $newUserId)->first();
        if (!$parent || !$child) {
            return;
        }

        $orderId = 'REF'.date('YmdHis').rand(1000, 9999);
        $opening = (float) $parent->wallet_balance;
        DB::table('users')->where('id', $parentId)->update([
            'wallet_balance' => $opening + $amount,
        ]);

        $row = [
            'user_id' => $parentId,
            'credit_user_id' => $newUserId,
            'debit_user_id' => 0,
            'amount' => $amount,
            'total_amount' => $amount,
            'fund_type' => 'Credit',
            'transaction_type' => 'Referral Bonus',
            'remark' => 'Referral bonus for '.$child->mobile_number,
            'order_id' => $orderId,
            'status' => 'Success',
            'opening_balance' => $opening,
            'closing_balance' => $opening + $amount,
            'transaction_date' => Carbon::now().':'.rand(111, 999),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        if (class_exists('\helpers') && method_exists('\helpers', 'filterReportColumns')) {
            $row = \helpers::filterReportColumns($row);
        }

        DB::table('reports')->insert($row);
    }
}
