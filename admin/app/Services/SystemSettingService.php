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
        ];
    }

    public static function ensureTable(): void
    {
        if (Schema::hasTable('system_settings')) {
            return;
        }

        Schema::create('system_settings', function ($table) {
            $table->id();
            $table->string('setting_key', 80)->unique();
            $table->text('setting_value')->nullable();
            $table->timestamps();
        });
    }

    public static function all(): array
    {
        self::ensureTable();
        $rows = DB::table('system_settings')->pluck('setting_value', 'setting_key')->toArray();

        return array_merge(self::defaults(), $rows);
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
