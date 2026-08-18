<?php

namespace App\Services;

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
            'fcm_server_key' => '',
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

    public static function putMany(array $data): void
    {
        self::ensureTable();
        $now = now();
        foreach ($data as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['setting_key' => $key],
                ['setting_value' => (string) $value, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }
}
