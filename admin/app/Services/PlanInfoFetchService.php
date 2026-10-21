<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlanInfoFetchService
{
    public const STOP_API_ID = 0;

    /** @var array<string, array{label: string, is_routing: bool, sort: int, default_primary: int|null, default_backup: int|null, default_backup2: int|null, default_backup3: int|null, default_cache_ttl: int, default_timeout: int}> */
    public const SERVICES = [
        'hlr' => [
            'label' => 'Operator/Circle Fetch (HLR)',
            'is_routing' => false,
            'sort' => 1,
            'default_primary' => 7,
            'default_backup' => 6,
            'default_backup2' => 8,
            'default_backup3' => self::STOP_API_ID,
            'default_cache_ttl' => 300,
            'default_timeout' => 25,
        ],
        'roffer_airtel' => [
            'label' => 'Routing R-Offer Fetch (Airtel)',
            'is_routing' => true,
            'sort' => 2,
            'default_primary' => 6,
            'default_backup' => self::STOP_API_ID,
            'default_backup2' => self::STOP_API_ID,
            'default_backup3' => self::STOP_API_ID,
            'default_cache_ttl' => 0,
            'default_timeout' => 30,
        ],
        'roffer_vi' => [
            'label' => 'Routing R-Offer Fetch (VI)',
            'is_routing' => true,
            'sort' => 3,
            'default_primary' => 6,
            'default_backup' => self::STOP_API_ID,
            'default_backup2' => self::STOP_API_ID,
            'default_backup3' => self::STOP_API_ID,
            'default_cache_ttl' => 0,
            'default_timeout' => 30,
        ],
        'roffer_jio' => [
            'label' => 'Routing R-Offer Fetch (Jio)',
            'is_routing' => true,
            'sort' => 4,
            'default_primary' => 6,
            'default_backup' => 7,
            'default_backup2' => 8,
            'default_backup3' => self::STOP_API_ID,
            'default_cache_ttl' => 0,
            'default_timeout' => 30,
        ],
        'roffer_bsnl' => [
            'label' => 'Routing R-Offer Fetch (BSNL)',
            'is_routing' => true,
            'sort' => 5,
            'default_primary' => 6,
            'default_backup' => 7,
            'default_backup2' => 8,
            'default_backup3' => self::STOP_API_ID,
            'default_cache_ttl' => 0,
            'default_timeout' => 30,
        ],
        'mobile_plan_retail' => [
            'label' => 'Mobile Plan/Roffer Fetch (Retail)',
            'is_routing' => false,
            'sort' => 6,
            'default_primary' => 6,
            'default_backup' => 7,
            'default_backup2' => 8,
            'default_backup3' => self::STOP_API_ID,
            'default_cache_ttl' => 600,
            'default_timeout' => 30,
        ],
        'last_recharge' => [
            'label' => 'Last Recharge / Expiry (Airtel & VI)',
            'is_routing' => false,
            'sort' => 7,
            'default_primary' => 6,
            'default_backup' => 7,
            'default_backup2' => 8,
            'default_backup3' => self::STOP_API_ID,
            'default_cache_ttl' => 120,
            'default_timeout' => 25,
        ],
        'dth_customer' => [
            'label' => 'DTH Customer Fetch',
            'is_routing' => false,
            'sort' => 8,
            'default_primary' => 6,
            'default_backup' => 7,
            'default_backup2' => 8,
            'default_backup3' => self::STOP_API_ID,
            'default_cache_ttl' => 120,
            'default_timeout' => 30,
        ],
        'dth_plan_list' => [
            'label' => 'DTH Plan List',
            'is_routing' => false,
            'sort' => 9,
            'default_primary' => 6,
            'default_backup' => 7,
            'default_backup2' => 8,
            'default_backup3' => self::STOP_API_ID,
            'default_cache_ttl' => 600,
            'default_timeout' => 30,
        ],
        'dth_heavy_refresh' => [
            'label' => 'DTH Heavy Refresh',
            'is_routing' => false,
            'sort' => 10,
            'default_primary' => 6,
            'default_backup' => 7,
            'default_backup2' => 8,
            'default_backup3' => self::STOP_API_ID,
            'default_cache_ttl' => 0,
            'default_timeout' => 45,
        ],
    ];

    public static function ensureTable(): void
    {
        if (!Schema::hasTable('plan_info_fetch_settings')) {
            try {
                Schema::create('plan_info_fetch_settings', function ($table) {
                    $table->id();
                    $table->string('service_key', 64)->unique();
                    $table->string('service_label');
                    $table->unsignedBigInteger('primary_api_id')->nullable();
                    $table->string('primary_username', 255)->nullable();
                    $table->string('primary_password', 255)->nullable();
                    $table->unsignedBigInteger('backup_api_id')->nullable();
                    $table->string('backup_username', 255)->nullable();
                    $table->string('backup_password', 255)->nullable();
                    $table->unsignedBigInteger('backup2_api_id')->nullable();
                    $table->string('backup2_username', 255)->nullable();
                    $table->string('backup2_password', 255)->nullable();
                    $table->unsignedBigInteger('backup3_api_id')->nullable();
                    $table->string('backup3_username', 255)->nullable();
                    $table->string('backup3_password', 255)->nullable();
                    $table->boolean('is_enabled')->default(true);
                    $table->unsignedInteger('cache_ttl')->default(0);
                    $table->unsignedSmallInteger('timeout_seconds')->default(30);
                    $table->unsignedInteger('success_count')->default(0);
                    $table->unsignedInteger('fail_count')->default(0);
                    $table->timestamp('last_success_at')->nullable();
                    $table->timestamp('last_fail_at')->nullable();
                    $table->string('last_message', 500)->nullable();
                    $table->boolean('is_routing')->default(false);
                    $table->unsignedTinyInteger('sort_order')->default(0);
                    $table->timestamps();
                });

                self::seedDefaults();
            } catch (\Throwable $e) {
                // Fall back to legacy API ids when table cannot be created.
            }
        }

        self::ensureExtraSettingsColumns();
    }

    private static function ensureExtraSettingsColumns(): void
    {
        if (!Schema::hasTable('plan_info_fetch_settings')) {
            return;
        }

        $columns = [
            'backup2_api_id' => fn ($table) => $table->unsignedBigInteger('backup2_api_id')->nullable(),
            'backup2_username' => fn ($table) => $table->string('backup2_username', 255)->nullable(),
            'backup2_password' => fn ($table) => $table->string('backup2_password', 255)->nullable(),
            'backup3_api_id' => fn ($table) => $table->unsignedBigInteger('backup3_api_id')->nullable(),
            'backup3_username' => fn ($table) => $table->string('backup3_username', 255)->nullable(),
            'backup3_password' => fn ($table) => $table->string('backup3_password', 255)->nullable(),
            'is_enabled' => fn ($table) => $table->boolean('is_enabled')->default(true),
            'cache_ttl' => fn ($table) => $table->unsignedInteger('cache_ttl')->default(0),
            'timeout_seconds' => fn ($table) => $table->unsignedSmallInteger('timeout_seconds')->default(30),
            'success_count' => fn ($table) => $table->unsignedInteger('success_count')->default(0),
            'fail_count' => fn ($table) => $table->unsignedInteger('fail_count')->default(0),
            'last_success_at' => fn ($table) => $table->timestamp('last_success_at')->nullable(),
            'last_fail_at' => fn ($table) => $table->timestamp('last_fail_at')->nullable(),
            'last_message' => fn ($table) => $table->string('last_message', 500)->nullable(),
        ];

        foreach ($columns as $name => $adder) {
            try {
                if (!Schema::hasColumn('plan_info_fetch_settings', $name)) {
                    Schema::table('plan_info_fetch_settings', function ($table) use ($adder) {
                        $adder($table);
                    });
                }
            } catch (\Throwable $e) {
                // Column may already exist on concurrent boot.
            }
        }
    }

    public static function seedDefaults(): void
    {
        self::ensureExtraSettingsColumns();

        foreach (self::SERVICES as $key => $meta) {
            $exists = DB::table('plan_info_fetch_settings')->where('service_key', $key)->exists();
            if ($exists) {
                DB::table('plan_info_fetch_settings')->where('service_key', $key)->update([
                    'service_label' => $meta['label'],
                    'is_routing' => $meta['is_routing'],
                    'sort_order' => $meta['sort'],
                    'updated_at' => now(),
                ]);
                continue;
            }

            DB::table('plan_info_fetch_settings')->insert([
                'service_key' => $key,
                'service_label' => $meta['label'],
                'primary_api_id' => $meta['default_primary'],
                'backup_api_id' => $meta['default_backup'],
                'backup2_api_id' => $meta['default_backup2'] ?? null,
                'backup3_api_id' => $meta['default_backup3'] ?? self::STOP_API_ID,
                'is_enabled' => true,
                'cache_ttl' => (int) ($meta['default_cache_ttl'] ?? 0),
                'timeout_seconds' => (int) ($meta['default_timeout'] ?? 30),
                'is_routing' => $meta['is_routing'],
                'sort_order' => $meta['sort'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public static function allSettings()
    {
        self::ensureTable();
        self::seedDefaults();

        return DB::table('plan_info_fetch_settings')->orderBy('sort_order')->get();
    }

    public static function settingsForDisplay()
    {
        $apis = self::apiOptions()->keyBy('id');

        return self::allSettings()->map(function ($row) use ($apis) {
            $row = (object) (array) $row;
            self::applyCredentialFallback($row, 'primary', $apis);
            self::applyCredentialFallback($row, 'backup', $apis);
            self::applyCredentialFallback($row, 'backup2', $apis);
            self::applyCredentialFallback($row, 'backup3', $apis);
            $row->is_enabled = (int) ($row->is_enabled ?? 1) === 1;
            $row->cache_ttl = (int) ($row->cache_ttl ?? 0);
            $row->timeout_seconds = (int) ($row->timeout_seconds ?? 30);
            $row->success_count = (int) ($row->success_count ?? 0);
            $row->fail_count = (int) ($row->fail_count ?? 0);

            return $row;
        });
    }

    /** @param \Illuminate\Support\Collection<int, object> $apis */
    private static function applyCredentialFallback(object $row, string $side, $apis): void
    {
        $apiIdKey = $side . '_api_id';
        $usernameKey = $side . '_username';
        $passwordKey = $side . '_password';
        $apiId = (int) ($row->{$apiIdKey} ?? 0);

        if ($apiId <= 0 || !isset($apis[$apiId])) {
            return;
        }

        $api = $apis[$apiId];

        if (empty($row->{$usernameKey})) {
            $row->{$usernameKey} = $api->api_key ?: $api->api_username ?: '';
        }

        if (empty($row->{$passwordKey})) {
            $row->{$passwordKey} = $api->api_password ?: $api->api_key ?: '';
        }
    }

    public static function saveSettings(array $rows): void
    {
        self::ensureTable();

        foreach ($rows as $row) {
            if (empty($row['service_key'])) {
                continue;
            }

            DB::table('plan_info_fetch_settings')->updateOrInsert(
                ['service_key' => $row['service_key']],
                [
                    'service_label' => $row['service_label'] ?? self::SERVICES[$row['service_key']]['label'] ?? $row['service_key'],
                    'primary_api_id' => self::nullableApiId($row['primary_api_id'] ?? null),
                    'primary_username' => self::nullableString($row['primary_username'] ?? null),
                    'primary_password' => self::nullableString($row['primary_password'] ?? null),
                    'backup_api_id' => self::nullableApiId($row['backup_api_id'] ?? null),
                    'backup_username' => self::nullableString($row['backup_username'] ?? null),
                    'backup_password' => self::nullableString($row['backup_password'] ?? null),
                    'backup2_api_id' => self::nullableApiId($row['backup2_api_id'] ?? null),
                    'backup2_username' => self::nullableString($row['backup2_username'] ?? null),
                    'backup2_password' => self::nullableString($row['backup2_password'] ?? null),
                    'backup3_api_id' => self::nullableApiId($row['backup3_api_id'] ?? null),
                    'backup3_username' => self::nullableString($row['backup3_username'] ?? null),
                    'backup3_password' => self::nullableString($row['backup3_password'] ?? null),
                    'is_enabled' => !array_key_exists('is_enabled', $row) || !empty($row['is_enabled']),
                    'cache_ttl' => max(0, (int) ($row['cache_ttl'] ?? 0)),
                    'timeout_seconds' => max(5, min(120, (int) ($row['timeout_seconds'] ?? 30))),
                    'is_routing' => !empty($row['is_routing']),
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Admin config test: verifies API 1–4 chain is resolvable.
     *
     * @return array{ok: bool, message: string, chain: list<array<string, mixed>>, api_id: int|null}
     */
    public static function testService(string $serviceKey): array
    {
        self::ensureTable();
        if (!isset(self::SERVICES[$serviceKey])) {
            return ['ok' => false, 'message' => 'Unknown service.', 'chain' => [], 'api_id' => null];
        }

        $row = DB::table('plan_info_fetch_settings')->where('service_key', $serviceKey)->first();
        if (!$row) {
            return ['ok' => false, 'message' => 'Service settings not found. Save/Reset first.', 'chain' => [], 'api_id' => null];
        }

        if ((int) ($row->is_enabled ?? 1) !== 1) {
            return ['ok' => false, 'message' => 'Service is disabled.', 'chain' => [], 'api_id' => null];
        }

        $slots = [
            ['label' => 'API 1', 'api_id' => $row->primary_api_id ?? null, 'username' => $row->primary_username ?? null, 'password' => $row->primary_password ?? null],
            ['label' => 'API 2', 'api_id' => $row->backup_api_id ?? null, 'username' => $row->backup_username ?? null, 'password' => $row->backup_password ?? null],
            ['label' => 'API 3', 'api_id' => $row->backup2_api_id ?? null, 'username' => $row->backup2_username ?? null, 'password' => $row->backup2_password ?? null],
            ['label' => 'API 4', 'api_id' => $row->backup3_api_id ?? null, 'username' => $row->backup3_username ?? null, 'password' => $row->backup3_password ?? null],
        ];

        $chain = [];
        $ready = 0;
        $firstReadyId = null;
        foreach ($slots as $slot) {
            $apiId = self::nullableApiId($slot['api_id']);
            if ($apiId === self::STOP_API_ID) {
                $chain[] = ['label' => $slot['label'], 'api_id' => 0, 'api_name' => 'Stop', 'ready' => false];
                break;
            }
            if ($apiId === null) {
                $chain[] = ['label' => $slot['label'], 'api_id' => null, 'api_name' => 'Auto / empty', 'ready' => false];
                continue;
            }
            $api = self::resolveApiRow($apiId, $slot['username'], $slot['password']);
            $isReady = (bool) $api;
            if ($isReady) {
                $ready++;
                $firstReadyId ??= (int) $api->id;
            }
            $chain[] = [
                'label' => $slot['label'],
                'api_id' => $apiId,
                'api_name' => $api->api_name ?? ('API #'.$apiId.' missing'),
                'ready' => $isReady,
            ];
        }

        $ok = $ready > 0;
        $message = $ok
            ? "Config OK — {$ready} API slot(s) ready. Cache {$row->cache_ttl}s, timeout {$row->timeout_seconds}s. Hits OK/Fail: ".(int) ($row->success_count ?? 0).'/'.(int) ($row->fail_count ?? 0)
            : 'No usable API in chain. Select API 1 (and backups) then Save.';

        return ['ok' => $ok, 'message' => $message, 'chain' => $chain, 'api_id' => $firstReadyId];
    }

    public static function rofferServiceKey(int $providerId): string
    {
        $name = strtolower((string) DB::table('providers')->where('id', $providerId)->value('provider_name'));

        if (str_contains($name, 'airtel')) {
            return 'roffer_airtel';
        }

        if ($name === 'vi' || str_contains($name, 'vodafone') || str_contains($name, 'idea')) {
            return 'roffer_vi';
        }

        return 'mobile_plan_retail';
    }

    public static function planServiceKey(int $providerId): string
    {
        $serviceId = (int) DB::table('providers')->where('id', $providerId)->value('service_id');

        return $serviceId === 2 ? 'dth_plan_list' : 'mobile_plan_retail';
    }

    /**
     * @param  callable(object): (string|null)  $urlBuilder
     * @return array{response: string, api_id: int}|null
     */
    public static function fetch(string $serviceKey, callable $urlBuilder, string $modal, string $orderPrefix): ?array
    {
        self::ensureTable();

        $setting = Schema::hasTable('plan_info_fetch_settings')
            ? DB::table('plan_info_fetch_settings')->where('service_key', $serviceKey)->first()
            : null;
        if (!$setting) {
            return self::legacyFetch($serviceKey, $urlBuilder, $modal, $orderPrefix);
        }

        if ((int) ($setting->is_enabled ?? 1) !== 1) {
            return null;
        }

        $attempts = [
            ['api_id' => $setting->primary_api_id, 'username' => $setting->primary_username, 'password' => $setting->primary_password],
            ['api_id' => $setting->backup_api_id, 'username' => $setting->backup_username, 'password' => $setting->backup_password],
            ['api_id' => $setting->backup2_api_id ?? null, 'username' => $setting->backup2_username ?? null, 'password' => $setting->backup2_password ?? null],
            ['api_id' => $setting->backup3_api_id ?? null, 'username' => $setting->backup3_username ?? null, 'password' => $setting->backup3_password ?? null],
        ];

        foreach ($attempts as $attempt) {
            $apiId = self::nullableApiId($attempt['api_id'] ?? null);
            if ($apiId === self::STOP_API_ID) {
                break;
            }

            $api = self::resolveApiRow($attempt['api_id'], $attempt['username'], $attempt['password']);
            if (!$api) {
                continue;
            }

            $url = $urlBuilder($api);
            if (!$url) {
                continue;
            }

            $orderId = $orderPrefix . random_int(1111111111, 9999999999);
            $result = \helpers::curl($url, 'GET', '', [], 'yes', $modal, $orderId);

            if ($result && self::isSuccessfulFetchResponse($result)) {
                $result['api_id'] = (int) $api->id;

                return $result;
            }
        }

        return null;
    }

    /**
     * @param  array{response?: mixed, code?: mixed, error?: mixed}  $result
     */
    private static function isSuccessfulFetchResponse(array $result): bool
    {
        if (empty($result['response'])) {
            return false;
        }

        $httpCode = (int) ($result['code'] ?? 0);
        if (in_array($httpCode, [404, 401, 403, 500, 502, 503], true)) {
            return false;
        }

        if (!empty($result['error']) && stripos((string) $result['error'], 'timed out') !== false) {
            return false;
        }

        $body = (string) $result['response'];
        $decoded = json_decode($body, true);

        return is_array($decoded);
    }

    /** @return array{response: string, api_id: int}|null */
    private static function legacyFetch(string $serviceKey, callable $urlBuilder, string $modal, string $orderPrefix): ?array
    {
        $legacyApiId = $serviceKey === 'hlr' ? 7 : 6;
        $api = self::resolveApiRow($legacyApiId, null, null);
        if (!$api) {
            return null;
        }

        $url = $urlBuilder($api);
        if (!$url) {
            return null;
        }

        $orderId = $orderPrefix . random_int(1111111111, 9999999999);
        $result = \helpers::curl($url, 'GET', '', [], 'yes', $modal, $orderId);
        if ($result && !empty($result['response'])) {
            $result['api_id'] = (int) $api->id;

            return $result;
        }

        return null;
    }

    /** @param  array{api_id?: mixed, username?: mixed, password?: mixed}  $attempt */
    public static function resolveApiRow($apiId, ?string $usernameOverride, ?string $passwordOverride): ?object
    {
        $apiId = self::nullableApiId($apiId);
        if ($apiId === null || $apiId === self::STOP_API_ID) {
            return null;
        }

        $api = DB::table('apis')->where('id', $apiId)->where('status', 1)->first();
        if (!$api) {
            $api = DB::table('apis')->where('id', $apiId)->first();
        }
        if (!$api) {
            return null;
        }

        $username = self::nullableString($usernameOverride);
        $password = self::nullableString($passwordOverride);

        $api->resolved_username = $username !== null && $username !== '' ? $username : ($api->api_username ?? '');
        $api->resolved_password = $password !== null && $password !== '' ? $password : ($api->api_password ?? '');
        $api->resolved_api_key = $password !== null && $password !== '' && empty($api->api_key)
            ? $password
            : ($api->api_key ?? '');

        // Honor Primary/Backup "Username / API Key" override when it looks like a token.
        if (!empty($username) && (empty($api->api_key) || strlen($username) >= 16) && !preg_match('/^\d{1,12}$/', $username)) {
            if (empty($api->api_key) || strlen($username) > 20 || preg_match('/^[0-9a-f-]{36}$/i', $username)) {
                $api->resolved_api_key = $username;
            }
        }

        return $api;
    }

    public static function apiOptions()
    {
        if (!Schema::hasTable('apis')) {
            return collect();
        }

        return DB::table('apis')->orderBy('api_name')->get(['id', 'api_name', 'api_url', 'api_username', 'api_password', 'api_key']);
    }

    public static function apiLabel(int $apiId): string
    {
        if ($apiId === self::STOP_API_ID) {
            return 'Stop R-Offer Check';
        }

        if ($apiId <= 0) {
            return 'Select Provider';
        }

        $name = DB::table('apis')->where('id', $apiId)->value('api_name');

        return $name ?: 'API #' . $apiId;
    }

    private static function nullableApiId($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private static function nullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
