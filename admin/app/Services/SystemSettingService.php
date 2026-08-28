<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemSettingService
{
    /** @var array{mode:string,key?:string,value?:string}|null */
    protected static ?array $storage = null;

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
            'user_login_method' => 'USER',
            'admin_login_method' => 'OTP',
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
                self::resolveStorage();

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
            self::$storage = ['mode' => 'kv', 'key' => 'setting_key', 'value' => 'setting_value'];
        } catch (\Throwable $e) {
            \Log::warning('system_settings create failed: '.$e->getMessage());
        }
    }

    /** @return array{mode:string,key?:string,value?:string} */
    protected static function resolveStorage(): array
    {
        if (self::$storage !== null) {
            return self::$storage;
        }

        foreach ([
            ['setting_key', 'setting_value'],
            ['key', 'value'],
            ['name', 'value'],
            ['setting_name', 'setting_value'],
        ] as [$keyCol, $valueCol]) {
            try {
                if (Schema::hasColumn('system_settings', $keyCol) && Schema::hasColumn('system_settings', $valueCol)) {
                    return self::$storage = ['mode' => 'kv', 'key' => $keyCol, 'value' => $valueCol];
                }
            } catch (\Throwable $e) {
            }
        }

        if (self::hasWideColumns()) {
            self::ensureWideColumns();

            return self::$storage = ['mode' => 'wide'];
        }

        self::addKeyValueColumns();

        return self::$storage = ['mode' => 'kv', 'key' => 'setting_key', 'value' => 'setting_value'];
    }

    protected static function hasWideColumns(): bool
    {
        foreach (array_keys(self::defaults()) as $settingKey) {
            try {
                if (Schema::hasColumn('system_settings', $settingKey)) {
                    return true;
                }
            } catch (\Throwable $e) {
            }
        }

        return false;
    }

    protected static function addKeyValueColumns(): void
    {
        try {
            if (! Schema::hasColumn('system_settings', 'setting_key')) {
                DB::statement('ALTER TABLE `system_settings` ADD COLUMN `setting_key` VARCHAR(80) NULL');
            }
            if (! Schema::hasColumn('system_settings', 'setting_value')) {
                DB::statement('ALTER TABLE `system_settings` ADD COLUMN `setting_value` TEXT NULL');
            }
        } catch (\Throwable $e) {
            \Log::warning('system_settings add kv columns failed: '.$e->getMessage());
        }
    }

    protected static function ensureWideColumns(): void
    {
        foreach (array_keys(self::defaults()) as $settingKey) {
            try {
                if (! Schema::hasColumn('system_settings', $settingKey)) {
                    DB::statement("ALTER TABLE `system_settings` ADD COLUMN `{$settingKey}` TEXT NULL");
                }
            } catch (\Throwable $e) {
            }
        }
    }

    public static function all(): array
    {
        static $memo = null;
        if (is_array($memo)) {
            return $memo;
        }

        try {
            $memo = \Illuminate\Support\Facades\Cache::remember('system_settings_all', 60, function () {
                self::ensureTable();
                $storage = self::resolveStorage();

                if ($storage['mode'] === 'wide') {
                    $row = DB::table('system_settings')->orderBy('id')->first();
                    if (! $row) {
                        return self::defaults();
                    }

                    $saved = [];
                    foreach (array_keys(self::defaults()) as $key) {
                        if (isset($row->{$key})) {
                            $saved[$key] = $row->{$key};
                        }
                    }

                    return array_merge(self::defaults(), $saved);
                }

                $rows = DB::table('system_settings')
                    ->whereNotNull($storage['key'])
                    ->where($storage['key'], '!=', '')
                    ->pluck($storage['value'], $storage['key'])
                    ->toArray();

                if (! is_array($rows)) {
                    $rows = [];
                }

                return array_merge(self::defaults(), $rows);
            });
        } catch (\Throwable $e) {
            \Log::warning('system_settings read failed: '.$e->getMessage());
            $memo = self::defaults();
        }

        return $memo;
    }

    public static function forgetCache(): void
    {
        try {
            \Illuminate\Support\Facades\Cache::forget('system_settings_all');
        } catch (\Throwable $e) {
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

    public static function userLoginRequiresOtp($user): bool
    {
        return strtoupper((string) ($user->login_type ?? '')) === 'OTP';
    }

    public static function adminLoginRequiresOtp(): bool
    {
        return strtoupper((string) self::get('admin_login_method', 'OTP')) !== 'PASSWORD';
    }

    public static function putMany(array $data): void
    {
        self::ensureTable();
        $storage = self::resolveStorage();
        $now = now();

        if ($storage['mode'] === 'wide') {
            self::ensureWideColumns();
            $payload = ['updated_at' => $now];
            foreach ($data as $key => $value) {
                if (Schema::hasColumn('system_settings', $key)) {
                    $payload[$key] = (string) $value;
                }
            }

            $existing = DB::table('system_settings')->orderBy('id')->first();
            if ($existing) {
                DB::table('system_settings')->where('id', $existing->id)->update($payload);
            } else {
                $payload['created_at'] = $now;
                DB::table('system_settings')->insert(self::withRequiredColumns($payload));
            }

            foreach ($data as $key => $value) {
                if (! Schema::hasColumn('system_settings', $key)) {
                    self::putKeyValueRow($key, (string) $value, $now);
                }
            }

            self::forgetCache();
            return;
        }

        foreach ($data as $key => $value) {
            self::putKeyValueRow($key, (string) $value, $now);
        }

        self::forgetCache();
    }

    protected static function putKeyValueRow(string $key, string $value, $now): void
    {
        $storage = self::resolveStorage();
        $keyCol = $storage['key'] ?? 'setting_key';
        $valueCol = $storage['value'] ?? 'setting_value';

        $payload = self::withRequiredColumns([
            $keyCol => $key,
            $valueCol => $value,
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        $existing = DB::table('system_settings')->where($keyCol, $key)->first();
        if ($existing) {
            unset($payload['created_at']);
            DB::table('system_settings')->where('id', $existing->id)->update($payload);

            return;
        }

        DB::table('system_settings')->insert($payload);
    }

    protected static function withRequiredColumns(array $payload): array
    {
        $known = [
            'status' => 1,
            'deleted_at' => 0,
            'company_id' => 1,
            'user_id' => 1,
            'created_by' => 1,
            'type' => 'system',
            'name' => $payload['setting_key'] ?? ($payload['key'] ?? 'setting'),
            'slug' => $payload['setting_key'] ?? ($payload['key'] ?? 'setting'),
            'title' => $payload['setting_key'] ?? ($payload['key'] ?? 'setting'),
        ];

        foreach ($known as $column => $value) {
            if (! array_key_exists($column, $payload)) {
                $payload[$column] = $value;
            }
        }

        try {
            $required = DB::select("
                SELECT COLUMN_NAME, DATA_TYPE
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'system_settings'
                  AND IS_NULLABLE = 'NO'
                  AND EXTRA NOT LIKE '%auto_increment%'
                  AND (COLUMN_DEFAULT IS NULL AND EXTRA NOT LIKE '%DEFAULT_GENERATED%')
            ");

            foreach ($required as $column) {
                $name = $column->COLUMN_NAME;
                if (array_key_exists($name, $payload)) {
                    continue;
                }

                $type = strtolower((string) $column->DATA_TYPE);
                if (in_array($type, ['int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'decimal', 'float', 'double'], true)) {
                    $payload[$name] = $name === 'status' ? 1 : 0;
                } elseif (in_array($type, ['datetime', 'timestamp', 'date'], true)) {
                    $payload[$name] = now();
                } else {
                    $payload[$name] = '';
                }
            }
        } catch (\Throwable $e) {
        }

        try {
            $columns = array_flip(Schema::getColumnListing('system_settings'));
            $payload = array_intersect_key($payload, $columns);
        } catch (\Throwable $e) {
        }

        return $payload;
    }
}
