<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    public static function rofferServiceKey(int $providerId): string
    {
        $name = strtolower((string) DB::table('providers')->where('id', $providerId)->value('provider_name'));

        if (str_contains($name, 'airtel')) {
            return 'roffer_airtel';
        }

        if ($name === 'vi' || str_contains($name, 'vodafone') || str_contains($name, 'idea')) {
            return 'roffer_vi';
        }

        if (str_contains($name, 'jio')) {
            return 'roffer_jio';
        }

        if (str_contains($name, 'bsnl')) {
            return 'roffer_bsnl';
        }

        return 'mobile_plan_retail';
    }

    public static function isServiceEnabled(string $serviceKey): bool
    {
        self::ensureTable();
        if (!Schema::hasTable('plan_info_fetch_settings')) {
            return true;
        }

        $row = DB::table('plan_info_fetch_settings')->where('service_key', $serviceKey)->first(['is_enabled']);
        if (!$row) {
            return true;
        }

        return (int) ($row->is_enabled ?? 1) === 1;
    }

    public static function recordServiceResult(string $serviceKey, bool $ok, ?string $message = null): void
    {
        if (!Schema::hasTable('plan_info_fetch_settings') || !Schema::hasColumn('plan_info_fetch_settings', 'success_count')) {
            return;
        }

        try {
            $payload = [
                'last_message' => $message !== null ? mb_substr(trim($message), 0, 500) : null,
                'updated_at' => now(),
            ];
            if ($ok) {
                $payload['success_count'] = DB::raw('success_count + 1');
                $payload['last_success_at'] = now();
            } else {
                $payload['fail_count'] = DB::raw('fail_count + 1');
                $payload['last_fail_at'] = now();
            }
            DB::table('plan_info_fetch_settings')->where('service_key', $serviceKey)->update($payload);
        } catch (\Throwable $e) {
            // Ignore stats write failures.
        }
    }

    private static function cacheTtlFor(?object $setting): int
    {
        return max(0, (int) ($setting->cache_ttl ?? 0));
    }

    private static function timeoutFor(?object $setting): int
    {
        $timeout = (int) ($setting->timeout_seconds ?? 0);
        if ($timeout < 5) {
            $timeout = (int) config('plan_api.timeout', 30);
        }

        return max(5, min(120, $timeout));
    }

    /**
     * Keep a multi-API plan chain inside a phone timeout.
     * One supplier may use the admin timeout. Two or more get a short try, then the next API.
     */
    private static function chainTimeout(int $configured, int $attemptCount): int
    {
        $configured = max(5, min(30, $configured));
        if ($attemptCount <= 1) {
            return $configured;
        }

        return min($configured, 8);
    }

    /** Jio / BSNL have no dedicated R-Offer service; fall back to the real plan list when no offers. */
    public static function rofferFallsBackToPlans(int $providerId): bool
    {
        $name = strtolower((string) DB::table('providers')->where('id', $providerId)->value('provider_name'));

        return str_contains($name, 'jio') || str_contains($name, 'bsnl');
    }

    /**
     * Flatten grouped plan/offer data and drop placeholder rows (no price, "not available", test text).
     *
     * @return list<array{rs: string, desc: string, validity: string, category?: string}>
     */
    public static function realOfferRows(array $data): array
    {
        $rows = [];
        if (self::isListArray($data)) {
            $rows = $data;
        } else {
            foreach ($data as $category => $list) {
                if (! is_array($list)) {
                    continue;
                }
                foreach ($list as $row) {
                    if (is_array($row)) {
                        $row['category'] = (string) $category;
                        $rows[] = $row;
                    }
                }
            }
        }

        $out = [];
        $seen = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $rs = trim((string) ($row['rs'] ?? ''));
            $desc = trim((string) ($row['desc'] ?? ''));
            if ($rs === '' || ! is_numeric($rs) || (float) $rs <= 0) {
                continue;
            }
            if ($desc === '' || preg_match('/dummy|demo|sample|test\b|not\s*available|no\s*(offer|plan|record|data)|not\s*found|invalid|not\s*authori[sz]e/i', $desc)) {
                continue;
            }
            $key = $rs.'|'.strtolower($desc);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = [
                'rs' => $rs,
                'desc' => $desc,
                'validity' => (string) ($row['validity'] ?? ''),
                'category' => (string) ($row['category'] ?? ''),
            ];
        }

        return $out;
    }

    public static function planServiceKey(int $providerId): string
    {
        $serviceId = (int) DB::table('providers')->where('id', $providerId)->value('service_id');

        return $serviceId === 2 ? 'dth_plan_list' : 'mobile_plan_retail';
    }

    /**
     * Fetch mobile recharge plans with env override, HTTP diagnostics, and logging.
     *
     * @return array{
     *   ok: bool,
     *   type: string,
     *   message: string,
     *   data: array,
     *   http_code: int|null,
     *   response_type: string,
     *   api_id: int|null,
     *   endpoint: string|null
     * }
     */
    public static function fetchMobilePlans(int $providerId, int $stateId): array
    {
        self::ensureTable();

        $state = DB::table('states')->where('id', $stateId)->first();
        if (!$state) {
            return self::planFetchResult(false, 'error', 'Invalid state selected. Circle/plan code is not configured.');
        }

        $serviceKey = self::planServiceKey($providerId);
        if (!self::isServiceEnabled($serviceKey)) {
            return self::planFetchResult(false, 'error', 'Plan fetch service is disabled by admin.');
        }

        $setting = Schema::hasTable('plan_info_fetch_settings')
            ? DB::table('plan_info_fetch_settings')->where('service_key', $serviceKey)->first()
            : null;

        $cacheTtl = self::cacheTtlFor($setting);
        $cacheKey = 'plan_fetch:mobile:'.$serviceKey.':'.$providerId.':'.$stateId;
        if ($cacheTtl > 0) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && !empty($cached['ok'])) {
                return $cached;
            }
        }

        $attempts = self::primaryBackupAttempts($setting, 6, true);
        $timeout = self::chainTimeout(self::timeoutFor($setting), count($attempts));

        $lastResult = null;

        foreach ($attempts as $index => $attempt) {
            $api = self::resolveApiRow($attempt['api_id'], $attempt['username'], $attempt['password']);
            if (!$api) {
                continue;
            }

            $operatorCode = self::resolveMobileOperatorCode($api, $providerId);
            if ($operatorCode === 0 || $operatorCode === '' || $operatorCode === null) {
                $lastResult = self::planFetchResult(
                    false,
                    'error',
                    'Operator code is not configured for this provider and plan API.',
                    [],
                    null,
                    'config',
                    (int) $api->id,
                    null
                );
                continue;
            }

            $circle = self::resolveCircleCode($api, $state);
            if ($circle === '') {
                $lastResult = self::planFetchResult(
                    false,
                    'error',
                    'Invalid state selected. Circle/plan code is not configured.',
                    [],
                    null,
                    'config',
                    (int) $api->id,
                    null
                );
                continue;
            }

            $url = self::buildMobilePlansUrl($api, (string) $operatorCode, $circle, false);
            if ($url === null) {
                $lastResult = self::planFetchResult(
                    false,
                    'error',
                    'Plan API key is not configured.',
                    [],
                    null,
                    'config',
                    (int) $api->id,
                    null
                );
                continue;
            }

            $orderId = 'ROP' . random_int(1111111111, 9999999999);
            $attemptLabel = self::attemptLabel($index);
            $requestResult = self::executePlanApiRequest($url, 'Plans', $orderId, [
                'operator' => (string) $operatorCode,
                'circle' => urldecode($circle),
                'provider_id' => $providerId,
                'state_id' => $stateId,
                'api_id' => (int) $api->id,
                'attempt' => $attemptLabel,
                'timeout' => $timeout,
            ]);

            $lastResult = $requestResult;

            if ($requestResult['ok']) {
                self::recordServiceResult($serviceKey, true, $requestResult['message'] ?? 'OK');
                if ($cacheTtl > 0) {
                    Cache::put($cacheKey, $requestResult, $cacheTtl);
                }
                return $requestResult;
            }

            if (self::shouldContinueFailover($attempts, $index)) {
                Log::info('Plan API attempt failed, trying next', [
                    'service_key' => $serviceKey,
                    'failed_api_id' => (int) $api->id,
                    'attempt' => $attemptLabel,
                    'http_code' => $requestResult['http_code'],
                    'message' => $requestResult['message'],
                ]);
                continue;
            }

            break;
        }

        if ($lastResult !== null) {
            self::recordServiceResult($serviceKey, false, $lastResult['message'] ?? null);
            return $lastResult;
        }

        $fail = self::planFetchResult(
            false,
            'error',
            'Unable to fetch plans. Check plan API settings and operator code.'
        );
        self::recordServiceResult($serviceKey, false, $fail['message']);

        return $fail;
    }

    public static function buildMobilePlansUrl(object $api, string $operatorCode, string $circleCode, bool $useEnvOverride = true): ?string
    {
        $circle = rawurldecode(str_replace('%20', ' ', $circleCode));
        $family = self::detectPlanFamily($api);
        $base = rtrim(self::resolvePlanApiBaseUrl($api, $useEnvOverride), '/');
        if ($base === '') {
            return null;
        }

        if ($family === 'planapi') {
            [$memberId, $password] = self::resolveHlrCredentials($api);
            if ($memberId === '' || $password === '') {
                return null;
            }

            return $base.'/Mobile/Operatorplan?apimember_id='.urlencode($memberId)
                .'&api_password='.urlencode($password)
                .'&cricle='.urlencode($circle)
                .'&operatorcode='.urlencode($operatorCode);
        }

        $key = $family === 'planconnect'
            ? self::resolvePlanConnectToken($api)
            : self::resolvePlanApiKey($api, $useEnvOverride);
        if ($key === null || $key === '') {
            return null;
        }

        if ($family === 'planconnect') {
            return $base.'/getMobilePlans?'.self::planConnectAuthQuery($key)
                .'&circleCode='.urlencode($circle)
                .'&operatorCode='.urlencode($operatorCode);
        }

        return $base.'/plans.php?apikey='.urlencode($key)
            .'&operator='.urlencode($operatorCode)
            .'&cricle='.urlencode($circle);
    }

    /**
     * R-Offer URL for PlanAPI (RofferCheck), MPlan (plans.php), or PlanConnect (getRoffers).
     */
    public static function buildRofferUrl(object $api, int $providerId, string $number): ?string
    {
        $operatorCode = self::resolveMobileOperatorCode($api, $providerId);
        if ($operatorCode === 0 || $operatorCode === '' || $operatorCode === null) {
            return null;
        }

        $family = self::detectPlanFamily($api);
        $base = rtrim(self::resolvePlanApiBaseUrl($api, false), '/');
        if ($base === '') {
            return null;
        }

        if ($family === 'planapi') {
            [$memberId, $password] = self::resolveHlrCredentials($api);
            if ($memberId === '' || $password === '') {
                return null;
            }

            return $base.'/Mobile/RofferCheck?apimember_id='.urlencode($memberId)
                .'&api_password='.urlencode($password)
                .'&operator_code='.urlencode((string) $operatorCode)
                .'&mobile_no='.urlencode($number);
        }

        $key = $family === 'planconnect'
            ? self::resolvePlanConnectToken($api)
            : self::resolvePlanApiKey($api, false);
        if ($key === null || $key === '') {
            return null;
        }

        if ($family === 'planconnect') {
            return $base.'/getRoffers?'.self::planConnectAuthQuery($key)
                .'&operatorCode='.urlencode((string) $operatorCode)
                .'&mobileNo='.urlencode($number)
                .'&mobile='.urlencode($number);
        }

        return $base.'/plans.php?apikey='.urlencode($key)
            .'&operator='.urlencode((string) $operatorCode)
            .'&offer=roffer&tel='.urlencode($number);
    }

    /**
     * PlanAPI OperatorFetchNew needs member ID + password, not the UUID API key.
     * Settings sometimes store the key as username; ignore UUID overrides.
     *
     * @return array{0: string, 1: string}
     */
    public static function resolveHlrCredentials(object $api): array
    {
        $memberId = trim((string) ($api->api_username ?? ''));
        $password = trim((string) ($api->api_password ?? ''));
        $overrideUser = trim((string) ($api->resolved_username ?? ''));
        $overridePass = trim((string) ($api->resolved_password ?? ''));

        if (self::isPlanApiMemberId($overrideUser)) {
            $memberId = $overrideUser;
        }

        if ($password === '' && $overridePass !== '' && !self::looksLikeUuid($overridePass)) {
            $password = $overridePass;
        }

        return [$memberId, $password];
    }

    /**
     * PlanConnect expects apiKey/token (not PlanAPI ApiUserID/ApiPassword).
     */
    public static function resolvePlanConnectToken(object $api): ?string
    {
        $key = self::resolvePlanApiKey($api, false);
        if ($key !== null && $key !== '') {
            return $key;
        }

        foreach ([
            $api->resolved_api_key ?? null,
            $api->api_key ?? null,
            $api->resolved_password ?? null,
            $api->api_password ?? null,
        ] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '' && strlen($candidate) >= 16 && ! self::isPlanApiMemberId($candidate)) {
                return $candidate;
            }
        }

        return self::extractApiKeyFromUrl((string) ($api->api_url ?? ''));
    }

    /** Query string fragment: apiKey + token (PlanConnect accepts either/both). */
    public static function planConnectAuthQuery(string $apiKey): string
    {
        $enc = urlencode($apiKey);

        return 'apiKey='.$enc.'&token='.$enc;
    }

    /** @return list<string> */
    public static function planConnectAuthHeaders(string $apiKey): array
    {
        return [
            'Authorization: Bearer '.$apiKey,
            'Accept: application/json',
        ];
    }

    public static function buildHlrUrl(object $api, string $number): ?string
    {
        $baseRaw = rtrim((string) ($api->api_url ?? ''), '/');
        if ($baseRaw === '') {
            return null;
        }

        $host = strtolower((string) (parse_url($baseRaw, PHP_URL_HOST) ?? ''));
        $base = self::normalizePlanApiBaseUrl($baseRaw);

        // PlanConnect: /api/operatorCheck?apiKey=&token=&mobileNo=
        if (self::isPlanConnectHost($host) || self::isPlanConnectHost($base)) {
            $token = self::resolvePlanConnectToken($api);
            if ($token === null || $token === '') {
                return null;
            }
            if ($base === '') {
                return null;
            }

            return $base.'/operatorCheck?'.self::planConnectAuthQuery($token)
                .'&mobileNo='.urlencode($number)
                .'&mobile='.urlencode($number);
        }

        if (str_contains($host, 'mplan')) {
            $key = self::resolvePlanApiKey($api, false);
            if ($key === null || $key === '' || $base === '') {
                return null;
            }

            return $base.'/operatorinfo.php?apikey='.urlencode($key)
                .'&tel='.urlencode($number);
        }

        [$memberId, $password] = self::resolveHlrCredentials($api);
        if ($memberId === '' || $password === '') {
            return null;
        }

        if ($host === 'planapi.in' || $host === 'www.planapi.in') {
            $base = 'https://planapi.in/api';
        } elseif ($base === '') {
            $base = $baseRaw;
        }

        return $base.'/Mobile/OperatorFetchNew?ApiUserID='.urlencode($memberId)
            .'&ApiPassword='.urlencode($password)
            .'&Mobileno='.urlencode($number);
    }

    /**
     * @return array{
     *   ok: bool,
     *   message: string,
     *   data: array,
     *   api_id: int|null,
     *   response: string|null
     * }
     */
    public static function fetchOperatorLookup(string $number): array
    {
        self::ensureTable();

        if (!self::isServiceEnabled('hlr')) {
            return [
                'ok' => false,
                'message' => 'Operator lookup service is disabled by admin.',
                'data' => [],
                'api_id' => null,
                'response' => null,
            ];
        }

        $setting = Schema::hasTable('plan_info_fetch_settings')
            ? DB::table('plan_info_fetch_settings')->where('service_key', 'hlr')->first()
            : null;

        $attempts = self::primaryBackupAttempts($setting, 7, true);

        $lastMessage = 'Unable to fetch operator details. Please try again.';
        $lastApiId = null;

        foreach ($attempts as $index => $attempt) {
            $api = self::resolveApiRow($attempt['api_id'], $attempt['username'], $attempt['password']);
            if (!$api) {
                continue;
            }

            $url = self::buildHlrUrl($api, $number);
            if ($url === null) {
                $lastMessage = self::isPlanConnectHost((string) ($api->api_url ?? ''))
                    ? 'Operator lookup API key/token is not configured for PlanConnect.'
                    : 'Operator lookup API credentials are not configured.';
                $lastApiId = (int) $api->id;
                continue;
            }

            $orderId = 'CMN' . random_int(1111111111, 9999999999);
            $headers = [];
            if (self::isPlanConnectHost($url)) {
                $token = self::resolvePlanConnectToken($api);
                if ($token) {
                    $headers = self::planConnectAuthHeaders($token);
                }
            }
            $result = \helpers::curl($url, 'GET', '', $headers, 'yes', 'CHECK_MOBILE', $orderId);
            $parsed = self::parseHlrResponse(is_array($result) ? $result : [], (int) $api->id);
            $lastApiId = (int) $api->id;
            $lastMessage = $parsed['message'];

            if ($parsed['ok']) {
                self::recordServiceResult('hlr', true, $lastMessage);
                return $parsed;
            }

            Log::info('HLR operator lookup failed', [
                'api_id' => (int) $api->id,
                'attempt' => self::attemptLabel($index),
                'http_code' => (int) ($result['code'] ?? 0),
                'message' => $lastMessage,
                'endpoint' => \helpers::redactUrlSecrets($url),
            ]);

            if (self::shouldContinueFailover($attempts, $index)) {
                continue;
            }

            break;
        }

        self::recordServiceResult('hlr', false, $lastMessage);

        return [
            'ok' => false,
            'message' => $lastMessage,
            'data' => [],
            'api_id' => $lastApiId,
            'response' => null,
        ];
    }

    /**
     * @return array{ok: bool, message: string, data: array, api_id: int|null, response: string|null}
     */
    public static function fetchDthService(string $serviceKey, int $providerId, string $number): array
    {
        self::ensureTable();

        if (!self::isServiceEnabled($serviceKey)) {
            return [
                'ok' => false,
                'message' => 'This DTH fetch service is disabled by admin.',
                'data' => [],
                'api_id' => null,
                'response' => null,
            ];
        }

        $setting = Schema::hasTable('plan_info_fetch_settings')
            ? DB::table('plan_info_fetch_settings')->where('service_key', $serviceKey)->first()
            : null;

        $attempts = self::primaryBackupAttempts(
            $setting,
            $serviceKey === 'dth_heavy_refresh' ? 6 : 7,
            true
        );

        $lastMessage = $serviceKey === 'dth_heavy_refresh'
            ? 'Unable to refresh DTH info. Please try again.'
            : 'Unable to fetch DTH info. Please try again.';
        $lastApiId = null;

        foreach ($attempts as $index => $attempt) {
            $api = self::resolveApiRow($attempt['api_id'], $attempt['username'], $attempt['password']);
            if (!$api) {
                continue;
            }

            $url = $serviceKey === 'dth_heavy_refresh'
                ? self::buildDthHeavyUrl($api, $providerId, $number)
                : self::buildDthInfoUrl($api, $providerId, $number);

            if ($url === null) {
                $lastMessage = 'DTH operator code is not mapped for this provider. Contact admin.';
                $lastApiId = (int) $api->id;
                continue;
            }

            $orderId = 'DTH' . random_int(1111111111, 9999999999);
            $modal = $serviceKey === 'dth_heavy_refresh' ? 'DTH HEAVY' : 'DTH INFO';
            $headers = [];
            if (self::isPlanConnectHost($url)) {
                $token = self::resolvePlanConnectToken($api);
                if ($token) {
                    $headers = self::planConnectAuthHeaders($token);
                }
            }
            $result = \helpers::curl($url, 'GET', '', $headers, 'yes', $modal, $orderId);
            $parsed = self::parseDthInfoResponse(is_array($result) ? $result : [], (int) $api->id);
            $lastApiId = (int) $api->id;
            $lastMessage = $parsed['message'];

            if ($parsed['ok']) {
                if ($serviceKey === 'dth_heavy_refresh' && trim((string) ($parsed['message'] ?? '')) === '') {
                    $parsed['message'] = 'Heavy Refresh Successfully Completed';
                }
                self::recordServiceResult($serviceKey, true, $parsed['message'] ?? 'OK');

                return $parsed;
            }

            Log::info('DTH info fetch failed', [
                'service_key' => $serviceKey,
                'api_id' => (int) $api->id,
                'attempt' => self::attemptLabel($index),
                'provider_id' => $providerId,
                'http_code' => (int) ($result['code'] ?? 0),
                'message' => $lastMessage,
                'endpoint' => \helpers::redactUrlSecrets($url),
            ]);

            if (self::shouldContinueFailover($attempts, $index)) {
                continue;
            }

            break;
        }

        self::recordServiceResult($serviceKey, false, $lastMessage);

        return [
            'ok' => false,
            'message' => $lastMessage,
            'data' => [],
            'api_id' => $lastApiId,
            'response' => null,
        ];
    }

    public static function buildDthInfoUrl(object $api, int $providerId, string $number): ?string
    {
        $providerName = (string) DB::table('providers')->where('id', $providerId)->value('provider_name');
        $rawUrl = (string) ($api->api_url ?? '');
        $host = strtolower((string) (parse_url($rawUrl, PHP_URL_HOST) ?? ''));

        if (str_contains($host, 'planapi.in')) {
            [$memberId, $password] = self::resolveHlrCredentials($api);
            $opcode = self::resolveDthOpcode($api->id, $providerId, $providerName, true);
            if ($memberId === '' || $password === '' || $opcode === null) {
                return null;
            }

            return 'https://planapi.in/api/Mobile/DTHINFOCheck?apimember_id=' . urlencode($memberId)
                . '&api_password=' . urlencode($password)
                . '&mobile_no=' . urlencode($number)
                . '&Opcode=' . urlencode($opcode);
        }

        $key = self::resolvePlanApiKey($api, false);
        $opcode = self::resolveDthOpcode($api->id, $providerId, $providerName, false);
        if ($key === null || $key === '' || $opcode === null) {
            return null;
        }

        $base = self::normalizePlanApiBaseUrl($rawUrl);
        if ($base === '') {
            return null;
        }

        // PlanConnect: /api/dthInfo?apiKey=&token=&vcNo=&operatorCode=
        if (self::isPlanConnectHost($host) || stripos($rawUrl, 'dthInfo') !== false) {
            return $base.'/dthInfo?'.self::planConnectAuthQuery($key)
                .'&vcNo='.urlencode($number)
                .'&operatorCode='.urlencode($opcode);
        }

        return $base . '/Dthinfo.php?apikey=' . urlencode($key)
            . '&operator=' . urlencode($opcode)
            . '&offer=roffer&tel=' . urlencode($number);
    }

    public static function buildDthHeavyUrl(object $api, int $providerId, string $number): ?string
    {
        $providerName = (string) DB::table('providers')->where('id', $providerId)->value('provider_name');
        $rawUrl = (string) ($api->api_url ?? '');
        $host = strtolower((string) (parse_url($rawUrl, PHP_URL_HOST) ?? ''));

        // PlanAPI has no dedicated heavy endpoint — use DTH info as soft-refresh failover.
        if (str_contains($host, 'planapi')) {
            return self::buildDthInfoUrl($api, $providerId, $number);
        }

        $key = self::resolvePlanApiKey($api, false);
        $opcode = self::resolveDthOpcode($api->id, $providerId, $providerName, false);
        if ($key === null || $key === '' || $opcode === null) {
            $opcode = self::resolveDthOpcode($api->id, $providerId, $providerName, true);
        }
        if ($key === null || $key === '' || $opcode === null) {
            return null;
        }

        $base = self::normalizePlanApiBaseUrl($rawUrl);
        if ($base === '') {
            return null;
        }

        // PlanConnect: /api/heavyRefresh?apiKey=&token=&vcNo=&operatorCode=
        if (self::isPlanConnectHost($base) || self::isPlanConnectHost($host)) {
            return $base.'/heavyRefresh?'.self::planConnectAuthQuery($key)
                .'&vcNo='.urlencode($number)
                .'&operatorCode='.urlencode($opcode);
        }

        // MPlan: /Dthheavy.php
        return $base . '/Dthheavy.php?apikey=' . urlencode($key)
            . '&operator=' . urlencode($opcode)
            . '&offer=roffer&tel=' . urlencode($number);
    }

    public static function buildLastRechargeUrl(object $api, int $providerId, string $number): ?string
    {
        $rawUrl = (string) ($api->api_url ?? '');
        $host = strtolower((string) (parse_url($rawUrl, PHP_URL_HOST) ?? ''));
        $base = self::normalizePlanApiBaseUrl($rawUrl);

        if (str_contains($host, 'planapi')) {
            [$memberId, $password] = self::resolveHlrCredentials($api);
            if ($memberId === '' || $password === '') {
                return null;
            }

            return 'https://planapi.in/api/Mobile/CheckLastRecharge?apimember_id='.urlencode($memberId)
                .'&api_password='.urlencode($password)
                .'&mobile_no='.urlencode($number);
        }

        $key = self::isPlanConnectHost($host) || self::isPlanConnectHost($base)
            ? self::resolvePlanConnectToken($api)
            : self::resolvePlanApiKey($api, false);
        if ($key === null || $key === '' || $base === '') {
            return null;
        }

        if (self::isPlanConnectHost($host) || self::isPlanConnectHost($base)) {
            return $base.'/getLastRecharge?'.self::planConnectAuthQuery($key)
                .'&mobileNo='.urlencode($number)
                .'&mobile='.urlencode($number);
        }

        return $base.'/Lastrecharge.php?apikey='.urlencode($key)
            .'&tel='.urlencode($number);
    }

    /**
     * @return array{ok: bool, message: string, data: array, api_id: int|null, response: string|null}
     */
    public static function fetchLastRecharge(int $providerId, string $number): array
    {
        self::ensureTable();
        $serviceKey = 'last_recharge';
        if (!self::isServiceEnabled($serviceKey)) {
            return ['ok' => false, 'message' => 'Last recharge service is disabled by admin.', 'data' => [], 'api_id' => null, 'response' => null];
        }

        $setting = Schema::hasTable('plan_info_fetch_settings')
            ? DB::table('plan_info_fetch_settings')->where('service_key', $serviceKey)->first()
            : null;
        $attempts = self::primaryBackupAttempts($setting, 6, true);
        $lastMessage = 'Unable to fetch last recharge details.';
        $lastApiId = null;

        foreach ($attempts as $index => $attempt) {
            $api = self::resolveApiRow($attempt['api_id'], $attempt['username'], $attempt['password']);
            if (!$api) {
                continue;
            }
            $url = self::buildLastRechargeUrl($api, $providerId, $number);
            if ($url === null) {
                $lastApiId = (int) $api->id;
                $lastMessage = 'Last recharge API is not configured for this provider.';
                continue;
            }

            $orderId = 'LRC'.random_int(1111111111, 9999999999);
            $headers = [];
            if (self::isPlanConnectHost($url)) {
                $token = self::resolvePlanConnectToken($api);
                if ($token) {
                    $headers = self::planConnectAuthHeaders($token);
                }
            }
            $result = \helpers::curl($url, 'GET', '', $headers, 'yes', 'LAST RECHARGE', $orderId);
            $parsed = self::parseDthInfoResponse(is_array($result) ? $result : [], (int) $api->id);
            $lastApiId = (int) $api->id;
            $lastMessage = $parsed['message'];
            if ($parsed['ok']) {
                self::recordServiceResult($serviceKey, true, $lastMessage);
                return $parsed;
            }
            if (self::shouldContinueFailover($attempts, $index)) {
                continue;
            }
            break;
        }

        self::recordServiceResult($serviceKey, false, $lastMessage);

        return ['ok' => false, 'message' => $lastMessage, 'data' => [], 'api_id' => $lastApiId, 'response' => null];
    }

    /**
     * Admin live test for a configured service chain.
     *
     * @param  array{number?: string, provider_id?: int, state_id?: int}  $params
     * @return array{ok: bool, message: string, chain: list<array<string, mixed>>, api_id: int|null}
     */
    public static function testService(string $serviceKey, array $params = []): array
    {
        self::ensureTable();
        if (!isset(self::SERVICES[$serviceKey])) {
            return ['ok' => false, 'message' => 'Unknown service key.', 'chain' => [], 'api_id' => null];
        }
        if (!self::isServiceEnabled($serviceKey)) {
            return ['ok' => false, 'message' => 'Service is disabled. Enable it and save first.', 'chain' => [], 'api_id' => null];
        }

        $setting = DB::table('plan_info_fetch_settings')->where('service_key', $serviceKey)->first();
        $fallback = $serviceKey === 'hlr' ? 7 : 6;
        $auto = !in_array($serviceKey, ['roffer_airtel', 'roffer_vi'], true);
        $attempts = self::primaryBackupAttempts($setting, $fallback, $auto);
        $chain = [];
        foreach ($attempts as $i => $attempt) {
            $api = self::resolveApiRow($attempt['api_id'], $attempt['username'], $attempt['password']);
            $chain[] = [
                'slot' => $i + 1,
                'label' => self::attemptLabel($i),
                'api_id' => $api ? (int) $api->id : self::nullableApiId($attempt['api_id']),
                'api_name' => $api->api_name ?? 'Missing / inactive API',
                'ready' => (bool) $api,
            ];
        }

        $number = preg_replace('/\D+/', '', (string) ($params['number'] ?? '9876543210')) ?: '9876543210';
        $providerId = (int) ($params['provider_id'] ?? 0);
        $stateId = (int) ($params['state_id'] ?? 0);

        if ($providerId <= 0) {
            $serviceId = str_starts_with($serviceKey, 'dth_') ? 2 : 1;
            $providerId = (int) (DB::table('providers')->where('service_id', $serviceId)->where('status', 1)->value('id') ?: 0);
        }
        if ($stateId <= 0) {
            $stateId = (int) (DB::table('states')->value('id') ?: 0);
        }

        $result = null;
        if ($serviceKey === 'hlr') {
            $result = self::fetchOperatorLookup($number);
        } elseif ($serviceKey === 'last_recharge') {
            $result = self::fetchLastRecharge($providerId, $number);
        } elseif (in_array($serviceKey, ['dth_customer', 'dth_heavy_refresh'], true)) {
            $result = self::fetchDthService($serviceKey, $providerId, $number);
        } elseif ($serviceKey === 'dth_plan_list') {
            $result = self::fetchDthPlans($providerId);
        } elseif ($serviceKey === 'mobile_plan_retail' || str_starts_with($serviceKey, 'roffer_')) {
            if ($stateId <= 0) {
                return ['ok' => false, 'message' => 'No state/circle configured for plan test.', 'chain' => $chain, 'api_id' => null];
            }
            $result = self::fetchMobilePlans($providerId > 0 ? $providerId : 1, $stateId);
        } else {
            return ['ok' => false, 'message' => 'Live test is not available for this service.', 'chain' => $chain, 'api_id' => null];
        }

        $ok = !empty($result['ok']);
        $message = (string) ($result['message'] ?? ($ok ? 'Test OK' : 'Test failed'));
        if ($ok && !empty($result['api_id'])) {
            $message .= ' (API #'.(int) $result['api_id'].')';
        }

        return [
            'ok' => $ok,
            'message' => $message,
            'chain' => $chain,
            'api_id' => isset($result['api_id']) ? (int) $result['api_id'] : null,
        ];
    }

    /**
     * @return array{
     *   ok: bool,
     *   type: string,
     *   message: string,
     *   data: array,
     *   http_code: int|null,
     *   response_type: string,
     *   api_id: int|null,
     *   endpoint: string|null
     * }
     */
    public static function fetchDthPlans(int $providerId): array
    {
        self::ensureTable();

        if (!self::isServiceEnabled('dth_plan_list')) {
            return self::planFetchResult(false, 'error', 'DTH plan list service is disabled by admin.');
        }

        $setting = Schema::hasTable('plan_info_fetch_settings')
            ? DB::table('plan_info_fetch_settings')->where('service_key', 'dth_plan_list')->first()
            : null;

        $cacheTtl = self::cacheTtlFor($setting);
        $cacheKey = 'plan_fetch:dth_plans:'.$providerId;
        if ($cacheTtl > 0) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && !empty($cached['ok'])) {
                return $cached;
            }
        }

        $attempts = self::primaryBackupAttempts($setting, 6, true);
        $timeout = self::chainTimeout(self::timeoutFor($setting), count($attempts));
        $lastResult = null;

        foreach ($attempts as $index => $attempt) {
            $api = self::resolveApiRow($attempt['api_id'], $attempt['username'], $attempt['password']);
            if (!$api) {
                continue;
            }

            $url = self::buildDthPlansUrl($api, $providerId);
            if ($url === null) {
                $lastResult = self::planFetchResult(
                    false,
                    'error',
                    'DTH operator code is not mapped for this provider. Contact admin.',
                    [],
                    null,
                    'config',
                    (int) $api->id,
                    null
                );
                continue;
            }

            $orderId = 'DPL' . random_int(1111111111, 9999999999);
            $requestResult = self::executePlanApiRequest($url, 'Plans', $orderId, [
                'provider_id' => $providerId,
                'api_id' => (int) $api->id,
                'attempt' => self::attemptLabel($index),
                'timeout' => $timeout,
            ]);

            if ($requestResult['ok'] && self::plansLookGrouped($requestResult['data'] ?? [])) {
                $requestResult['data'] = self::expandDthDurationPrices($requestResult['data']);
                self::recordServiceResult('dth_plan_list', true, $requestResult['message'] ?? 'OK');
                if ($cacheTtl > 0) {
                    Cache::put($cacheKey, $requestResult, $cacheTtl);
                }
                return $requestResult;
            }

            if ($requestResult['ok'] && !empty($requestResult['data'])) {
                $requestResult['data'] = ['Plans' => array_values($requestResult['data'])];
                self::recordServiceResult('dth_plan_list', true, $requestResult['message'] ?? 'OK');
                if ($cacheTtl > 0) {
                    Cache::put($cacheKey, $requestResult, $cacheTtl);
                }
                return $requestResult;
            }

            $lastResult = $requestResult;

            if (self::shouldContinueFailover($attempts, $index)) {
                continue;
            }

            break;
        }

        if ($lastResult !== null) {
            self::recordServiceResult('dth_plan_list', false, $lastResult['message'] ?? null);
            return $lastResult;
        }

        $fail = self::planFetchResult(false, 'error', 'Unable to fetch DTH plans. Check plan API settings and operator code.');
        self::recordServiceResult('dth_plan_list', false, $fail['message']);

        return $fail;
    }

    public static function buildDthPlansUrl(object $api, int $providerId): ?string
    {
        $providerName = (string) DB::table('providers')->where('id', $providerId)->value('provider_name');
        $rawUrl = (string) ($api->api_url ?? '');
        $host = strtolower((string) (parse_url($rawUrl, PHP_URL_HOST) ?? ''));

        if (str_contains($host, 'planapi.in')) {
            [$memberId, $password] = self::resolveHlrCredentials($api);
            $opcode = self::resolveDthOpcode($api->id, $providerId, $providerName, true);
            if ($memberId === '' || $password === '' || $opcode === null) {
                return null;
            }

            return 'https://planapi.in/api/Mobile/DthPlans?apimember_id=' . urlencode($memberId)
                . '&api_password=' . urlencode($password)
                . '&operatorcode=' . urlencode($opcode);
        }

        $key = self::resolvePlanApiKey($api, false);
        $opcode = self::resolveDthOpcode($api->id, $providerId, $providerName, false);
        if ($key === null || $key === '' || $opcode === null) {
            return null;
        }

        $base = self::normalizePlanApiBaseUrl($rawUrl);
        if ($base === '') {
            return null;
        }

        // PlanConnect docs: /api/getDthPlans?apiKey=&token=&operatorCode=
        if (self::isPlanConnectHost($host) || self::isPlanConnectHost($base)) {
            return $base.'/getDthPlans?'.self::planConnectAuthQuery($key)
                .'&operatorCode='.urlencode($opcode);
        }

        return $base . '/dthplans.php?apikey=' . urlencode($key)
            . '&operator=' . urlencode($opcode);
    }

    /**
     * MPlan dthplans.php returns rs as {"1 MONTHS": "153", ...}; flatten to one row per duration.
     *
     * @param  array<string, mixed>  $grouped
     * @return array<string, mixed>
     */
    private static function expandDthDurationPrices(array $grouped): array
    {
        foreach ($grouped as $category => $rows) {
            if (!is_array($rows)) {
                continue;
            }

            $flat = [];
            foreach ($rows as $row) {
                if (!is_array($row) || !is_array($row['rs'] ?? null)) {
                    $flat[] = $row;
                    continue;
                }

                $desc = trim((string) ($row['plan_name'] ?? ''));
                $extra = trim((string) ($row['desc'] ?? ''));
                if ($extra !== '' && $extra !== $desc) {
                    $desc = $desc !== '' ? $desc . ' - ' . $extra : $extra;
                }

                foreach ($row['rs'] as $duration => $amount) {
                    $flat[] = [
                        'rs' => preg_replace('/[^\d.]/', '', (string) $amount) ?? '',
                        'desc' => $desc,
                        'validity' => (string) $duration,
                    ];
                }
            }

            $grouped[$category] = $flat;
        }

        return $grouped;
    }

    /** @param  array<string, mixed>  $data */
    private static function plansLookGrouped(array $data): bool
    {
        if ($data === [] || array_key_exists(0, $data)) {
            return false;
        }

        $first = reset($data);

        return is_array($first);
    }

    public static function resolvePlanApiBaseUrl(object $api, bool $useEnvOverride = true): string
    {
        $fromApi = self::normalizePlanApiBaseUrl((string) ($api->api_url ?? ''));
        if ($fromApi !== '') {
            return $fromApi;
        }

        if ($useEnvOverride) {
            $envBase = trim((string) config('plan_api.base_url', ''));
            if ($envBase !== '') {
                return rtrim($envBase, '/');
            }
        }

        return '';
    }

    /**
     * Turn a full endpoint / template URL into a clean API base (scheme + host + /api).
     * Prevents: .../dthInfo?apiKey=...[vcNo].../plans.php?...
     */
    public static function normalizePlanApiBaseUrl(?string $apiUrl): string
    {
        $raw = trim((string) $apiUrl);
        if ($raw === '') {
            return '';
        }

        $parts = parse_url($raw);
        if (! is_array($parts) || empty($parts['host'])) {
            return rtrim(preg_replace('/\?.*$/', '', $raw) ?: $raw, '/');
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = strtolower((string) $parts['host']);
        $path = (string) ($parts['path'] ?? '');

        // Drop endpoint file / action segments.
        $path = preg_replace(
            '#/(?:dthInfo|getDthPlans|getMobilePlans|getRoffers|heavyRefresh|operatorCheck|Dthinfo\.php|Dthheavy\.php|dthplans\.php|plans\.php|roffer\.php|Mobile(?:/[^/]+)*)/?$#i',
            '',
            $path
        ) ?: $path;
        $path = rtrim($path, '/');

        if ($path === '' || ! preg_match('#/api$#i', $path)) {
            if (preg_match('#^(.*?/api)(?:/|$)#i', (string) ($parts['path'] ?? ''), $m)) {
                $path = rtrim($m[1], '/');
            } elseif (str_contains($host, 'planconnect') || str_contains($host, 'mplan') || str_contains($host, 'planapi')) {
                $path = '/api';
            }
        }

        return rtrim($scheme.'://'.$parts['host'].$path, '/');
    }

    /** Pull apiKey from a stored template URL when apis.api_key is empty. */
    public static function extractApiKeyFromUrl(?string $apiUrl): ?string
    {
        $query = parse_url((string) $apiUrl, PHP_URL_QUERY);
        if (! is_string($query) || $query === '') {
            return null;
        }
        parse_str($query, $params);
        foreach (['apiKey', 'apikey', 'api_key', 'token', 'key'] as $name) {
            $val = trim((string) ($params[$name] ?? ''));
            if ($val !== '' && ! preg_match('/^\[[^\]]+\]$/', $val)) {
                return $val;
            }
        }

        return null;
    }

    public static function resolvePlanApiKey(object $api, bool $useEnvOverride = true): ?string
    {
        if ($useEnvOverride) {
            $envKey = trim((string) config('plan_api.api_key', ''));
            if ($envKey !== '') {
                return $envKey;
            }
        }

        $key = trim((string) ($api->resolved_api_key ?? $api->api_key ?? ''));
        if ($key !== '') {
            return $key;
        }

        return self::extractApiKeyFromUrl((string) ($api->api_url ?? ''));
    }

    private static function isPlanConnectHost(?string $hostOrUrl): bool
    {
        $host = strtolower((string) (parse_url((string) $hostOrUrl, PHP_URL_HOST) ?? $hostOrUrl));

        return str_contains($host, 'planconnect');
    }

    /** @return 'planapi'|'planconnect'|'mplan' */
    public static function detectPlanFamily(object $api): string
    {
        $hay = strtolower(trim((string) ($api->api_url ?? '').' '.(string) ($api->api_name ?? '')));
        if (str_contains($hay, 'planconnect')) {
            return 'planconnect';
        }
        if (str_contains($hay, 'mplan')) {
            return 'mplan';
        }
        if (str_contains($hay, 'planapi')) {
            return 'planapi';
        }

        return 'mplan';
    }

    /**
     * Build ordered API chain: Primary → Backup → Backup2 (PlanConnect / MPlan / PlanAPI).
     * Null backup slots auto-fill the next unused plan-provider family when $autoBackup is true.
     * Backup api_id = 0 (STOP) ends the chain (no further failover).
     *
     * @param  object|null  $setting
     * @return list<array{api_id: mixed, username: mixed, password: mixed}>
     */
    private static function primaryBackupAttempts(?object $setting, int $fallbackPrimary, bool $autoBackup = false): array
    {
        $slots = $setting
            ? [
                ['api_id' => $setting->primary_api_id ?? null, 'username' => $setting->primary_username ?? null, 'password' => $setting->primary_password ?? null],
                ['api_id' => $setting->backup_api_id ?? null, 'username' => $setting->backup_username ?? null, 'password' => $setting->backup_password ?? null],
                ['api_id' => $setting->backup2_api_id ?? null, 'username' => $setting->backup2_username ?? null, 'password' => $setting->backup2_password ?? null],
                ['api_id' => $setting->backup3_api_id ?? null, 'username' => $setting->backup3_username ?? null, 'password' => $setting->backup3_password ?? null],
            ]
            : [
                ['api_id' => $fallbackPrimary, 'username' => null, 'password' => null],
                ['api_id' => null, 'username' => null, 'password' => null],
                ['api_id' => null, 'username' => null, 'password' => null],
                ['api_id' => null, 'username' => null, 'password' => null],
            ];

        $attempts = [];

        foreach ($slots as $slotIndex => $slot) {
            $apiId = self::nullableApiId($slot['api_id'] ?? null);

            if ($slotIndex === 0) {
                if ($apiId === null || $apiId === self::STOP_API_ID) {
                    $apiId = $fallbackPrimary;
                }
                if ($apiId !== null && $apiId !== self::STOP_API_ID) {
                    $attempts[] = [
                        'api_id' => $apiId,
                        'username' => $slot['username'],
                        'password' => $slot['password'],
                    ];
                }
                continue;
            }

            if ($apiId === self::STOP_API_ID) {
                break;
            }

            if ($apiId !== null) {
                $attempts[] = [
                    'api_id' => $apiId,
                    'username' => $slot['username'],
                    'password' => $slot['password'],
                ];
                continue;
            }

            if ($autoBackup) {
                $usedIds = array_column($attempts, 'api_id');
                $autoId = self::autoBackupPlanApiIdExcluding($usedIds);
                if ($autoId !== null) {
                    $attempts[] = ['api_id' => $autoId, 'username' => null, 'password' => null];
                }
            }
        }

        return $attempts;
    }

    /**
     * Plan APIs are stored with api_type "others", so this cannot rely on apiOptions().
     * Only APIs whose URL/name explicitly names a plan provider qualify; a different
     * provider family than ones already used is preferred.
     *
     * @param  list<int|string|null>  $usedApiIds
     */
    private static function autoBackupPlanApiIdExcluding(array $usedApiIds): ?int
    {
        $usedIdSet = [];
        $usedFamilies = [];

        foreach ($usedApiIds as $rawId) {
            $id = self::nullableApiId($rawId);
            if ($id === null) {
                continue;
            }
            $usedIdSet[$id] = true;
            $row = DB::table('apis')->where('id', $id)->first(['id', 'api_name', 'api_url']);
            if ($row) {
                $family = self::explicitPlanFamily($row);
                if ($family !== null) {
                    $usedFamilies[$family] = true;
                }
            }
        }

        $candidates = DB::table('apis')->where('status', 1)->orderBy('id')->get(['id', 'api_name', 'api_url']);
        foreach ($candidates as $api) {
            $id = (int) $api->id;
            if (isset($usedIdSet[$id])) {
                continue;
            }
            $family = self::explicitPlanFamily($api);
            if ($family === null || isset($usedFamilies[$family])) {
                continue;
            }

            return $id;
        }

        return null;
    }

    /** @deprecated Use autoBackupPlanApiIdExcluding */
    private static function autoBackupPlanApiId($primaryApiId): ?int
    {
        return self::autoBackupPlanApiIdExcluding([$primaryApiId]);
    }

    private static function attemptLabel(int $index): string
    {
        return match ($index) {
            0 => 'primary',
            1 => 'backup',
            2 => 'backup2',
            default => 'backup3',
        };
    }

    /**
     * @param  list<array{api_id: mixed, username: mixed, password: mixed}>  $attempts
     */
    private static function shouldContinueFailover(array $attempts, int $index): bool
    {
        for ($i = $index + 1; $i < count($attempts); $i++) {
            if (self::shouldRetryWithBackupApi($attempts[$i]['api_id'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private static function explicitPlanFamily(object $api): ?string
    {
        $hay = strtolower(trim((string) ($api->api_url ?? '').' '.(string) ($api->api_name ?? '')));
        foreach (['planconnect', 'mplan', 'planapi', 'planinfo', 'a1plan'] as $family) {
            if (str_contains($hay, $family)) {
                return $family;
            }
        }

        return null;
    }

    private static function resolveCircleCode(object $api, object $state): string
    {
        $family = self::detectPlanFamily($api);
        if ($family === 'planapi') {
            return self::planApiCircleCode($state);
        }

        $circle = trim((string) ($state->mplan_state_code ?? ''));
        if ($circle === '') {
            $circle = trim((string) ($state->state_name ?? ''));
        }

        return $circle;
    }

    private static function planApiCircleCode(object $state): string
    {
        $code = trim((string) ($state->plan_api_code ?? ''));
        if ($code !== '' && preg_match('/^\d+$/', $code)) {
            return $code;
        }

        $name = preg_replace('/[^a-z]/', '', strtolower((string) ($state->state_name ?? $state->mplan_state_code ?? ''))) ?? '';
        $map = [
            'delhi' => '10',
            'delhincr' => '10',
            'upwest' => '97',
            'upw' => '97',
            'uttarpradeshwest' => '97',
            'punjab' => '02',
            'hp' => '03',
            'himachal' => '03',
            'himachalpradesh' => '03',
            'haryana' => '96',
            'jk' => '55',
            'jammuandkashmir' => '55',
            'jammukashmir' => '55',
            'upeast' => '54',
            'upe' => '54',
            'uttarpradesheast' => '54',
            'mumbai' => '92',
            'maharashtra' => '90',
            'maharashtragoa' => '90',
            'gujarat' => '98',
            'mp' => '93',
            'madhyapradesh' => '93',
            'rajasthan' => '70',
            'kolkatta' => '31',
            'kolkata' => '31',
            'westbengal' => '51',
            'orissa' => '53',
            'odisha' => '53',
            'assam' => '56',
            'nesa' => '16',
            'northeast' => '16',
            'ne' => '16',
            'bihar' => '52',
            'biharjharkhand' => '52',
            'karnataka' => '06',
            'chennai' => '40',
            'tamilnadu' => '94',
            'tn' => '94',
            'kerala' => '95',
            'ap' => '49',
            'andhrapradesh' => '49',
            'andhrapradeshtelangana' => '49',
            'telangana' => '49',
            'sikkim' => '99',
            'tripura' => '100',
            'chhatisgarh' => '101',
            'chhattisgarh' => '101',
            'goa' => '102',
            'meghalay' => '103',
            'meghalaya' => '103',
            'mizzoram' => '104',
            'mizoram' => '104',
            'jharkhand' => '105',
            'manipur' => '106',
        ];

        if ($name !== '' && isset($map[$name])) {
            return $map[$name];
        }

        if ($code !== '') {
            return $code;
        }

        return trim((string) ($state->state_name ?? ''));
    }

    public static function resolveMobileOperatorCode(object $api, int $providerId)
    {
        $family = self::detectPlanFamily($api);
        $ids = [(int) $api->id];
        foreach (self::apiOptions() as $row) {
            $id = (int) $row->id;
            if ($id === (int) $api->id) {
                continue;
            }
            if (self::detectPlanFamily($row) === $family) {
                $ids[] = $id;
            }
        }

        foreach ($ids as $apiId) {
            $row = DB::table('api_provider_codes')
                ->where('provider_id', $providerId)
                ->where('api_id', $apiId)
                ->first();
            if ($row && $row->provider_code !== null && trim((string) $row->provider_code) !== '' && (string) $row->provider_code !== '0') {
                $code = trim((string) $row->provider_code);
                if ($family === 'planapi' && ! is_numeric($code)) {
                    $fallback = self::planApiPrepaidOpcodeFromProvider($providerId);
                    if ($fallback !== null) {
                        return $fallback;
                    }
                }
                if ($family !== 'planapi' && is_numeric($code) && strlen($code) <= 3) {
                    $named = self::mplanOperatorFromProvider($providerId);
                    if ($named !== null) {
                        return $named;
                    }
                }

                return $code;
            }
        }

        if ($family === 'planapi') {
            return self::planApiPrepaidOpcodeFromProvider($providerId) ?? 0;
        }

        return self::mplanOperatorFromProvider($providerId) ?? 0;
    }

    private static function planApiPrepaidOpcodeFromProvider(int $providerId): ?string
    {
        $name = strtolower((string) DB::table('providers')->where('id', $providerId)->value('provider_name'));
        if (str_contains($name, 'airtel')) {
            return '2';
        }
        if (str_contains($name, 'jio') || str_contains($name, 'reliance')) {
            return '11';
        }
        if ($name === 'vi' || str_contains($name, 'vodafone') || str_contains($name, 'idea')) {
            return '23';
        }
        if (str_contains($name, 'bsnl')) {
            return '4';
        }

        return null;
    }

    private static function mplanOperatorFromProvider(int $providerId): ?string
    {
        $name = strtolower((string) DB::table('providers')->where('id', $providerId)->value('provider_name'));
        if (str_contains($name, 'airtel')) {
            return 'Airtel';
        }
        if (str_contains($name, 'jio') || str_contains($name, 'reliance')) {
            return 'Jio';
        }
        if ($name === 'vi' || str_contains($name, 'vodafone') || str_contains($name, 'idea')) {
            return 'Voda';
        }
        if (str_contains($name, 'bsnl')) {
            return 'BSNL';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{
     *   ok: bool,
     *   type: string,
     *   message: string,
     *   data: array,
     *   http_code: int|null,
     *   response_type: string,
     *   api_id: int|null,
     *   endpoint: string|null
     * }
     */
    public static function executePlanApiRequest(string $url, string $modal, string $orderId, array $context = []): array
    {
        $endpoint = \helpers::redactUrlSecrets($url);
        $apiId = isset($context['api_id']) ? (int) $context['api_id'] : null;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_ENCODING, '');
        $timeout = isset($context['timeout']) ? (int) $context['timeout'] : (int) config('plan_api.timeout', 30);
        $timeout = max(5, min(120, $timeout));
        $connectTimeout = min(5, $timeout);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');

        $headers = ['Accept: application/json'];
        if (self::isPlanConnectHost($url)) {
            $token = null;
            $query = parse_url($url, PHP_URL_QUERY);
            if (is_string($query)) {
                parse_str($query, $params);
                $token = trim((string) ($params['token'] ?? $params['apiKey'] ?? $params['apikey'] ?? ''));
            }
            if ($token !== '') {
                $headers = array_merge($headers, self::planConnectAuthHeaders($token));
            }
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $responseBody = is_string($response) ? $response : '';
        $responseType = self::detectResponseType($responseBody, $httpCode);

        self::logPlanApiRequest($endpoint, $context, $httpCode, $responseType, $responseBody, $curlError);

        try {
            DB::table('apilogs')->insert([
                'url' => $endpoint,
                'modal' => $modal,
                'txnid' => $orderId,
                'header' => json_encode([]),
                'request' => json_encode($context),
                'response' => self::sanitizeResponseBody($responseBody),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // API logging should never break the primary request flow.
        }

        if ($curlError !== '') {
            $message = stripos($curlError, 'timed out') !== false
                ? 'API timeout'
                : 'Plan API request failed: ' . $curlError;

            return self::planFetchResult(false, 'error', $message, [], $httpCode ?: null, $responseType, $apiId, $endpoint);
        }

        if ($httpCode === 404) {
            return self::planFetchResult(false, 'error', 'Plan API endpoint not found', [], 404, $responseType, $apiId, $endpoint);
        }

        if (in_array($httpCode, [401, 403], true)) {
            return self::planFetchResult(false, 'error', 'Plan API authentication error', [], $httpCode, $responseType, $apiId, $endpoint);
        }

        if ($responseBody === '') {
            return self::planFetchResult(false, 'error', 'Empty response from Plan API', [], $httpCode ?: null, 'empty', $apiId, $endpoint);
        }

        if ($responseType !== 'json') {
            return self::planFetchResult(false, 'error', 'Invalid API response', [], $httpCode ?: null, $responseType, $apiId, $endpoint);
        }

        $decoded = json_decode($responseBody, true);
        if (!is_array($decoded)) {
            return self::planFetchResult(false, 'error', 'Invalid API response', [], $httpCode ?: null, 'json', $apiId, $endpoint);
        }

        if (self::isAuthorizationFailure($decoded)) {
            return self::planFetchResult(
                false,
                'error',
                'Plan API authentication error',
                [],
                $httpCode ?: null,
                'json',
                $apiId,
                $endpoint
            );
        }

        if (self::isErrorResponse($decoded)) {
            $apiMessage = self::responseErrorMessage($responseBody) ?: 'Plan API returned an error.';

            return self::planFetchResult(false, 'error', $apiMessage, [], $httpCode ?: null, 'json', $apiId, $endpoint);
        }

        $records = self::extractRecords($responseBody);
        if ($records === []) {
            $apiMessage = self::responseErrorMessage($responseBody);

            return self::planFetchResult(
                false,
                'error',
                $apiMessage ?: 'No plans found for this operator and circle.',
                [],
                $httpCode ?: null,
                'json',
                $apiId,
                $endpoint
            );
        }

        return self::planFetchResult(true, 'success', 'Fatch Successfully', $records, $httpCode ?: null, 'json', $apiId, $endpoint);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function logPlanApiRequest(
        string $endpoint,
        array $context,
        int $httpCode,
        string $responseType,
        string $responseBody,
        string $curlError
    ): void {
        Log::info('Plan API request', [
            'endpoint' => $endpoint,
            'operator' => $context['operator'] ?? null,
            'circle' => $context['circle'] ?? null,
            'provider_id' => $context['provider_id'] ?? null,
            'state_id' => $context['state_id'] ?? null,
            'api_id' => $context['api_id'] ?? null,
            'http_code' => $httpCode,
            'response_type' => $responseType,
            'curl_error' => $curlError !== '' ? $curlError : null,
            'response_body' => self::sanitizeResponseBody($responseBody),
        ]);
    }

    private static function detectResponseType(string $body, int $httpCode): string
    {
        if ($body === '') {
            return 'empty';
        }

        $trimmed = ltrim($body);
        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            return json_decode($body, true) !== null ? 'json' : 'invalid_json';
        }

        if (stripos($body, '<html') !== false || stripos($body, '<!DOCTYPE') !== false) {
            return 'html';
        }

        if ($httpCode === 404) {
            return 'html';
        }

        return 'text';
    }

    private static function sanitizeResponseBody(string $body): string
    {
        if ($body === '') {
            return '';
        }

        $redacted = (string) preg_replace(
            '/((?:api[_-]?key|api[_-]?password|apitoken|token|password))[=:]\s*["\']?[^"\'&\s]+/i',
            '$1=[REDACTED]',
            $body
        );

        return strlen($redacted) > 2000 ? substr($redacted, 0, 2000) . '...[truncated]' : $redacted;
    }

    private static function isAuthorizationFailure(array $data): bool
    {
        foreach (['message', 'Message', 'error', 'Error', 'msg'] as $key) {
            if (!empty($data[$key]) && is_string($data[$key]) && self::looksLikeAuthMessage($data[$key])) {
                return true;
            }
        }

        if (isset($data['records']) && is_array($data['records'])) {
            foreach (['msg', 'message', 'Message', 'error'] as $key) {
                if (!empty($data['records'][$key]) && is_string($data['records'][$key]) && self::looksLikeAuthMessage($data['records'][$key])) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function looksLikeAuthMessage(string $message): bool
    {
        $message = strtolower($message);

        return str_contains($message, 'not authorize')
            || str_contains($message, 'unauthorized')
            || str_contains($message, 'authentication')
            || str_contains($message, 'token missing')
            || str_contains($message, 'invalid api key')
            || str_contains($message, 'invalid apikey')
            || str_contains($message, 'invalid ip');
    }

    /**
     * @return array{
     *   ok: bool,
     *   type: string,
     *   message: string,
     *   data: array,
     *   http_code: int|null,
     *   response_type: string,
     *   api_id: int|null,
     *   endpoint: string|null
     * }
     */
    private static function planFetchResult(
        bool $ok,
        string $type,
        string $message,
        array $data = [],
        ?int $httpCode = null,
        string $responseType = 'unknown',
        ?int $apiId = null,
        ?string $endpoint = null
    ): array {
        return [
            'ok' => $ok,
            'type' => $type,
            'message' => $message,
            'data' => $data,
            'http_code' => $httpCode,
            'response_type' => $responseType,
            'api_id' => $apiId,
            'endpoint' => $endpoint,
        ];
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
        if (!self::isServiceEnabled($serviceKey)) {
            return null;
        }

        $allowAutoBackup = in_array($serviceKey, [
            'mobile_plan_retail', 'dth_plan_list', 'hlr', 'dth_customer', 'dth_heavy_refresh',
            'last_recharge', 'roffer_jio', 'roffer_bsnl',
        ], true);
        $fallback = $serviceKey === 'hlr' ? 7 : 6;
        $attempts = self::primaryBackupAttempts($setting, $fallback, $allowAutoBackup);

        foreach ($attempts as $index => $attempt) {
            $api = self::resolveApiRow($attempt['api_id'], $attempt['username'], $attempt['password']);
            if (!$api) {
                continue;
            }

            $url = $urlBuilder($api);
            if (!$url) {
                continue;
            }

            $orderId = $orderPrefix . random_int(1111111111, 9999999999);
            $headers = [];
            if (self::isPlanConnectHost($url)) {
                $token = self::resolvePlanConnectToken($api);
                if ($token) {
                    $headers = self::planConnectAuthHeaders($token);
                }
            }
            $result = \helpers::curl($url, 'GET', '', $headers, 'yes', $modal, $orderId);

            if ($result && self::isSuccessfulFetchResponse($result) && self::extractRecords($result['response'] ?? null) !== []) {
                $result['api_id'] = (int) $api->id;
                self::recordServiceResult($serviceKey, true, 'OK');

                return $result;
            }

            if (self::shouldContinueFailover($attempts, $index)) {
                Log::info('Plan info fetch attempt failed, trying next', [
                    'service_key' => $serviceKey,
                    'failed_api_id' => (int) $api->id,
                    'attempt' => self::attemptLabel($index),
                    'http_code' => (int) ($result['code'] ?? 0),
                    'endpoint' => isset($url) ? \helpers::redactUrlSecrets($url) : null,
                ]);
            }
        }

        self::recordServiceResult($serviceKey, false, 'All plan API attempts failed');

        return null;
    }

    private static function shouldRetryWithBackupApi($backupApiId): bool
    {
        $backupApiId = self::nullableApiId($backupApiId);

        return $backupApiId !== null && $backupApiId !== self::STOP_API_ID;
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
        if (!is_array($decoded)) {
            return false;
        }

        if (self::isErrorResponse($decoded) || self::isAuthorizationFailure($decoded)) {
            return false;
        }

        return true;
    }

    public static function extractRecords(?string $response): array
    {
        if ($response === null || $response === '') {
            return [];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return [];
        }

        if (self::isErrorResponse($data)) {
            return [];
        }

        $dthPlans = self::normalizeDthPlanRecords($data);
        if ($dthPlans !== []) {
            return $dthPlans;
        }

        $records = $data['records']
            ?? $data['RDATA']
            ?? $data['DATA']
            ?? $data['Roffer']
            ?? $data['roffer']
            ?? $data['Plans']
            ?? null;

        if ($records === null && isset($data['data']) && is_array($data['data'])) {
            $inner = $data['data'];
            // PlanConnect ROffer: { data: { rOffers: [ { amount, description } ] } }
            foreach (['records', 'RDATA', 'DATA', 'rOffers', 'ROffers', 'offers', 'Roffer', 'roffer', 'plans'] as $key) {
                if (isset($inner[$key]) && is_array($inner[$key])) {
                    $records = $inner[$key];
                    break;
                }
            }
            if ($records === null) {
                $records = $inner;
            }
        }

        // Single wrapper object → unwrap list (e.g. only key is rOffers).
        if (is_array($records) && ! self::isListArray($records) && count($records) === 1) {
            $only = reset($records);
            if (is_array($only) && self::isListArray($only)) {
                $records = $only;
            }
        }

        if (! is_array($records)) {
            return [];
        }

        return self::normalizePlanRecordTree($records);
    }

    /**
     * Map PlanConnect/MPlan rows to portal shape: rs / desc / validity.
     * Supports flat ROffer lists and category maps (FULLTT, TOPUP, …).
     *
     * @param  array<mixed>  $records
     * @return array<mixed>
     */
    private static function normalizePlanRecordTree(array $records): array
    {
        if ($records === []) {
            return [];
        }

        if (self::isListArray($records)) {
            $out = [];
            foreach ($records as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $normalized = self::normalizePlanRow($row);
                if ($normalized !== null) {
                    $out[] = $normalized;
                }
            }

            return $out;
        }

        $out = [];
        foreach ($records as $category => $rows) {
            if (! is_array($rows)) {
                continue;
            }
            $normalizedRows = [];
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $normalized = self::normalizePlanRow($row);
                if ($normalized !== null) {
                    $normalizedRows[] = $normalized;
                }
            }
            if ($normalizedRows !== []) {
                $label = is_string($category) && $category !== '' ? $category : 'Plans';
                $out[$label] = $normalizedRows;
            }
        }

        return $out;
    }

    /** @param  array<string, mixed>  $row */
    private static function normalizePlanRow(array $row): ?array
    {
        $rs = $row['rs']
            ?? $row['amount']
            ?? $row['Amount']
            ?? $row['price']
            ?? $row['Price']
            ?? $row['recharge_amount']
            ?? '';
        $rs = preg_replace('/[^\d.]/', '', (string) $rs) ?? '';

        $desc = $row['desc']
            ?? $row['description']
            ?? $row['Description']
            ?? $row['ofrtext']
            ?? $row['logdesc']
            ?? $row['offer']
            ?? $row['Offer']
            ?? $row['plan_name']
            ?? $row['PlanName']
            ?? $row['name']
            ?? '';
        $desc = trim((string) $desc);

        $validity = $row['validity']
            ?? $row['Validity']
            ?? $row['valid']
            ?? $row['Valid']
            ?? '';

        if ($rs === '' && $desc === '') {
            return null;
        }

        return [
            'rs' => $rs,
            'desc' => $desc,
            'validity' => (string) $validity,
        ];
    }

    /** @param  array<mixed>  $arr */
    private static function isListArray(array $arr): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($arr);
        }

        if ($arr === []) {
            return true;
        }

        return array_keys($arr) === range(0, count($arr) - 1);
    }

    /**
     * PlanConnect getDthPlans → group into rs/desc/validity rows.
     * Supports both shapes:
     * - { name, description, priceList:[{amount,validity}] }
     * - { name, description, amount, validity }  (flat, one row per item)
     *
     * @param  array<string, mixed>  $data
     * @return array<string, array<int, array{rs: string, desc: string, validity: string}>>
     */
    private static function normalizePlanConnectDthPlans(array $data): array
    {
        $plans = $data['data']['plans'] ?? $data['plans'] ?? null;
        if (! is_array($plans) || $plans === []) {
            return [];
        }

        $first = reset($plans);
        if (! is_array($first)) {
            return [];
        }
        $looksLikePlanConnect = isset($first['priceList'])
            || isset($first['name'])
            || isset($first['amount'])
            || isset($first['description']);
        if (! $looksLikePlanConnect) {
            return [];
        }

        $out = [];
        foreach ($plans as $pack) {
            if (! is_array($pack)) {
                continue;
            }
            $category = trim((string) ($pack['language'] ?? $pack['category'] ?? 'Plans'));
            if ($category === '') {
                $category = 'Plans';
            }
            $name = trim((string) ($pack['name'] ?? ''));
            $description = trim((string) ($pack['description'] ?? ''));
            $desc = trim($name.($description !== '' ? ' - '.$description : ''));
            $priceList = $pack['priceList'] ?? $pack['PricingList'] ?? null;

            if (is_array($priceList) && $priceList !== []) {
                foreach ($priceList as $price) {
                    if (! is_array($price)) {
                        continue;
                    }
                    $amount = preg_replace('/[^\d.]/', '', (string) ($price['amount'] ?? $price['Amount'] ?? $price['rs'] ?? '')) ?? '';
                    $out[$category][] = [
                        'rs' => $amount,
                        'desc' => $desc !== '' ? $desc : $name,
                        'validity' => (string) ($price['validity'] ?? $price['Month'] ?? ''),
                    ];
                }
                continue;
            }

            // Flat PlanConnect row: amount/validity on the pack itself.
            if (isset($pack['amount']) || isset($pack['validity']) || $desc !== '') {
                $amount = preg_replace('/[^\d.]/', '', (string) ($pack['amount'] ?? $pack['rs'] ?? '')) ?? '';
                $out[$category][] = [
                    'rs' => $amount,
                    'desc' => $desc !== '' ? $desc : $name,
                    'validity' => (string) ($pack['validity'] ?? ''),
                ];
            }
        }

        return $out;
    }

    /**
     * PlanAPI DthPlans returns RDATA with nested PricingList, not rs/desc/validity.
     * PlanConnect getDthPlans returns data.plans[{name,description,priceList,language}].
     *
     * @param  array<string, mixed>  $data
     * @return array<string, array<int, array{rs: string, desc: string, validity: string}>>
     */
    private static function normalizeDthPlanRecords(array $data): array
    {
        $planConnect = self::normalizePlanConnectDthPlans($data);
        if ($planConnect !== []) {
            return $planConnect;
        }

        $source = $data['RDATA'] ?? $data['rdata'] ?? null;
        if (!is_array($source) || $source === []) {
            return [];
        }

        $out = [];
        foreach ($source as $category => $packs) {
            if (!is_array($packs)) {
                continue;
            }

            $rows = [];
            foreach ($packs as $pack) {
                if (!is_array($pack)) {
                    continue;
                }

                if (isset($pack['rs']) || isset($pack['desc'])) {
                    $rows[] = [
                        'rs' => (string) ($pack['rs'] ?? ''),
                        'desc' => (string) ($pack['desc'] ?? $pack['PlanName'] ?? ''),
                        'validity' => (string) ($pack['validity'] ?? ''),
                    ];
                    continue;
                }

                $details = $pack['Details'] ?? null;
                if (!is_array($details) || $details === []) {
                    $details = [$pack];
                }

                foreach ($details as $detail) {
                    if (!is_array($detail)) {
                        continue;
                    }
                    $name = trim((string) ($detail['PlanName'] ?? $detail['desc'] ?? ''));
                    $channels = trim((string) ($detail['Channels'] ?? ''));
                    $desc = trim($name . ($channels !== '' ? ' - ' . $channels : ''));
                    $pricing = $detail['PricingList'] ?? null;
                    if (is_array($pricing) && $pricing !== []) {
                        foreach ($pricing as $price) {
                            if (!is_array($price)) {
                                continue;
                            }
                            $amount = preg_replace('/[^\d.]/', '', (string) ($price['Amount'] ?? $price['rs'] ?? '')) ?? '';
                            $rows[] = [
                                'rs' => $amount,
                                'desc' => $desc,
                                'validity' => (string) ($price['Month'] ?? $price['validity'] ?? ''),
                            ];
                        }
                        continue;
                    }

                    if ($desc !== '' || isset($detail['rs'])) {
                        $rows[] = [
                            'rs' => (string) ($detail['rs'] ?? ''),
                            'desc' => $desc !== '' ? $desc : (string) ($detail['desc'] ?? ''),
                            'validity' => (string) ($detail['validity'] ?? ''),
                        ];
                    }
                }
            }

            $label = is_string($category) && $category !== '' ? $category : 'Plans';
            if ($rows !== []) {
                $out[$label] = $rows;
            }
        }

        return $out;
    }

    public static function responseErrorMessage(?string $response): ?string
    {
        if ($response === null || $response === '') {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return null;
        }

        foreach (['message', 'Message', 'MESSAGE', 'msg'] as $key) {
            if (!empty($data[$key]) && is_string($data[$key])) {
                return $data[$key];
            }
        }

        foreach (['error', 'Error', 'ERROR'] as $key) {
            if (!empty($data[$key]) && is_string($data[$key]) && !preg_match('/^\d+$/', $data[$key])) {
                return $data[$key];
            }
        }

        if (isset($data['records']) && is_array($data['records'])) {
            foreach (['msg', 'message', 'Message', 'error', 'Error'] as $key) {
                if (!empty($data['records'][$key]) && is_string($data['records'][$key])) {
                    return $data['records'][$key];
                }
            }
        }

        return null;
    }

    private static function isErrorResponse(array $data): bool
    {
        $errorCode = $data['ERROR'] ?? $data['error'] ?? null;
        $hasPayload = ! empty($data['DATA'])
            || ! empty($data['RDATA'])
            || ! empty($data['records'])
            || ! empty($data['Plans'])
            || ! empty($data['data'])
            || ! empty($data['Roffer'])
            || ! empty($data['roffer']);
        if ($errorCode !== null && in_array((string) $errorCode, ['0', '', 'false'], true) && $hasPayload) {
            return false;
        }

        if ($errorCode !== null && !in_array((string) $errorCode, ['0', '', 'false'], true)) {
            return true;
        }

        if (isset($data['status']) && !$hasPayload) {
            $status = strtolower((string) $data['status']);
            if (in_array($status, ['0', 'false', 'failed', 'fail', 'error'], true)) {
                return true;
            }
        }

        if (isset($data['Response']) && in_array($data['Response'], ['Fail', 'Failed', 'Error'], true)) {
            return true;
        }

        if (isset($data['success']) && in_array($data['success'], [false, 0, '0', 'false'], true)) {
            return true;
        }

        if (isset($data['ERROR']) && !in_array((string) $data['ERROR'], ['0', '', 'false'], true)) {
            return true;
        }

        return false;
    }

    /**
     * @param  array{response?: mixed, code?: mixed, error?: mixed}  $result
     * @return array{ok: bool, message: string, data: array, api_id: int|null, response: string|null}
     */
    private static function parseHlrResponse(array $result, int $apiId): array
    {
        $httpCode = (int) ($result['code'] ?? 0);
        $curlError = trim((string) ($result['error'] ?? ''));
        $body = is_string($result['response'] ?? null) ? $result['response'] : '';

        if ($curlError !== '') {
            $message = stripos($curlError, 'timed out') !== false
                ? 'Operator lookup timed out. Please try again.'
                : 'Operator lookup request failed.';

            return self::hlrResult(false, $message, [], $apiId, $body);
        }

        if (in_array($httpCode, [401, 403], true)) {
            return self::hlrResult(false, 'Operator lookup authentication failed. Check PlanAPI credentials.', [], $apiId, $body);
        }

        if ($httpCode === 500) {
            return self::hlrResult(
                false,
                'Operator lookup API rejected the request. Check PlanAPI member ID and password.',
                [],
                $apiId,
                $body
            );
        }

        if ($httpCode === 404) {
            return self::hlrResult(false, 'Operator lookup endpoint was not found.', [], $apiId, $body);
        }

        if ($body === '') {
            return self::hlrResult(false, 'Empty response from operator lookup API.', [], $apiId, $body);
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            return self::hlrResult(false, 'Invalid response from operator lookup API.', [], $apiId, $body);
        }

        $apiMessage = self::responseErrorMessage($body);
        if (self::isAuthorizationFailure($data) || self::isErrorResponse($data)) {
            return self::hlrResult(false, self::formatHlrErrorMessage($apiMessage, $httpCode), [], $apiId, $body);
        }

        // MPlan operatorinfo.php nests the result under "records".
        if (is_array($data['records'] ?? null) && !isset($data['data'])) {
            $data['data'] = $data['records'];
        }

        $opCode = self::hlrNonEmpty(
            $data['OpCode']
            ?? $data['opcode']
            ?? $data['OperatorCode']
            ?? $data['operatorCode']
            ?? ($data['data']['OpCode'] ?? null)
            ?? ($data['data']['operatorCode'] ?? null)
            ?? ($data['data']['OperatorCode'] ?? null)
        );
        $circleCode = self::hlrNonEmpty(
            $data['CircleCode']
            ?? $data['circlecode']
            ?? $data['circleCode']
            ?? ($data['data']['CircleCode'] ?? null)
            ?? ($data['data']['circleCode'] ?? null)
        );
        $operatorName = self::hlrNonEmpty(
            $data['Operator']
            ?? $data['operator']
            ?? $data['operatorName']
            ?? ($data['data']['Operator'] ?? null)
            ?? ($data['data']['operator'] ?? null)
            ?? ($data['data']['operatorName'] ?? null)
        );
        $circleName = self::hlrNonEmpty(
            $data['Circle']
            ?? $data['circle']
            ?? $data['circleName']
            ?? ($data['data']['Circle'] ?? null)
            ?? ($data['data']['circle'] ?? null)
            ?? ($data['data']['circleName'] ?? null)
        );

        if ($opCode === null && $operatorName === null) {
            $message = ($apiMessage !== null && $apiMessage !== '')
                ? self::formatHlrErrorMessage($apiMessage, $httpCode)
                : 'Operator or circle not found for this number.';

            return self::hlrResult(false, $message, $data, $apiId, $body);
        }

        if ($circleCode === null && $circleName === null) {
            $message = ($apiMessage !== null && $apiMessage !== '')
                ? self::formatHlrErrorMessage($apiMessage, $httpCode)
                : 'Operator or circle not found for this number.';

            return self::hlrResult(false, $message, $data, $apiId, $body);
        }

        if ($opCode !== null) {
            $data['OpCode'] = $opCode;
        }
        if ($circleCode !== null) {
            $data['CircleCode'] = $circleCode;
        }
        if ($operatorName !== null) {
            $data['Operator'] = $operatorName;
        }
        if ($circleName !== null) {
            $data['Circle'] = $circleName;
        }

        return self::hlrResult(true, 'Get Successfully', $data, $apiId, $body);
    }

    /**
     * @return array{ok: bool, message: string, data: array, api_id: int|null, response: string|null}
     */
    private static function hlrResult(bool $ok, string $message, array $data, ?int $apiId, ?string $response): array
    {
        return [
            'ok' => $ok,
            'message' => $message !== '' ? $message : 'Unable to fetch operator details. Please try again.',
            'data' => $data,
            'api_id' => $apiId,
            'response' => $response,
        ];
    }

    private static function formatHlrErrorMessage(?string $message, int $httpCode = 0): string
    {
        $message = trim((string) $message);
        if ($message !== '' && stripos($message, 'invalid ip') !== false) {
            if (preg_match('/\b(?:\d{1,3}\.){3}\d{1,3}\b/', $message, $match)) {
                return 'Operator lookup IP is not authorized at PlanAPI. Whitelist this IP: ' . $match[0];
            }

            return 'Operator lookup IP is not authorized at PlanAPI. Ask admin to whitelist this server.';
        }

        if ($httpCode === 500) {
            return 'Operator lookup API rejected the request. Check PlanAPI member ID and password.';
        }

        return $message !== '' ? $message : 'Unable to fetch operator details. Please try again.';
    }

    private static function looksLikeUuid(string $value): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value);
    }

    private static function isPlanApiMemberId(string $value): bool
    {
        return $value !== '' && !self::looksLikeUuid($value) && strlen($value) <= 32;
    }

    /**
     * PlanAPI DTH opcodes: 24 Airtel, 25 Dish, 27 Sun, 28 Tata Sky, 29 Videocon.
     * MPlan Dthinfo.php uses operator names such as Tatasky.
     */
    private static function resolveDthOpcode($apiId, int $providerId, string $providerName, bool $planApiNumeric): ?string
    {
        $mapped = \helpers::PlanProviderCode($apiId, $providerId);
        if ($mapped !== 0 && $mapped !== '' && $mapped !== null && (string) $mapped !== '0') {
            if ($planApiNumeric && !is_numeric((string) $mapped)) {
                return self::planApiDthOpcode($providerName);
            }
            if (!$planApiNumeric && is_numeric((string) $mapped)) {
                return self::mplanDthOperator($providerName) ?? (string) $mapped;
            }

            return (string) $mapped;
        }

        return $planApiNumeric
            ? self::planApiDthOpcode($providerName)
            : self::mplanDthOperator($providerName);
    }

    private static function planApiDthOpcode(string $providerName): ?string
    {
        $name = strtolower($providerName);
        if (str_contains($name, 'tata')) {
            return '28';
        }
        if (str_contains($name, 'airtel')) {
            return '24';
        }
        if (str_contains($name, 'dish')) {
            return '25';
        }
        if (str_contains($name, 'sun')) {
            return '27';
        }
        if (str_contains($name, 'videocon') || str_contains($name, 'd2h')) {
            return '29';
        }
        if (str_contains($name, 'big tv') || str_contains($name, 'bigtv')) {
            return '26';
        }

        return null;
    }

    private static function mplanDthOperator(string $providerName): ?string
    {
        $name = strtolower($providerName);
        if (str_contains($name, 'tata')) {
            return 'Tatasky';
        }
        if (str_contains($name, 'airtel')) {
            return 'Airteldth';
        }
        if (str_contains($name, 'dish')) {
            return 'Dishtv';
        }
        if (str_contains($name, 'sun')) {
            return 'Sundirect';
        }
        if (str_contains($name, 'videocon') || str_contains($name, 'd2h')) {
            return 'Videocon';
        }

        return null;
    }

    /**
     * @param  array{response?: mixed, code?: mixed, error?: mixed}  $result
     * @return array{ok: bool, message: string, data: array, api_id: int|null, response: string|null}
     */
    private static function parseDthInfoResponse(array $result, int $apiId): array
    {
        $httpCode = (int) ($result['code'] ?? 0);
        $curlError = trim((string) ($result['error'] ?? ''));
        $body = is_string($result['response'] ?? null) ? $result['response'] : '';

        if ($curlError !== '') {
            $message = stripos($curlError, 'timed out') !== false
                ? 'DTH info request timed out. Please try again.'
                : 'DTH info request failed.';

            return self::hlrResult(false, $message, [], $apiId, $body);
        }

        if (in_array($httpCode, [401, 403], true)) {
            return self::hlrResult(false, 'DTH info authentication failed. Check API credentials.', [], $apiId, $body);
        }

        if ($httpCode === 404) {
            return self::hlrResult(false, 'DTH info endpoint was not found.', [], $apiId, $body);
        }

        if ($httpCode === 500) {
            return self::hlrResult(false, 'DTH info API rejected the request. Check credentials and operator code.', [], $apiId, $body);
        }

        if ($body === '') {
            return self::hlrResult(false, 'Empty response from DTH info API.', [], $apiId, $body);
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            return self::hlrResult(false, 'Invalid response from DTH info API.', [], $apiId, $body);
        }

        $apiMessage = self::responseErrorMessage($body);
        $errorCode = $data['error'] ?? $data['ERROR'] ?? null;
        if ($errorCode !== null && !in_array((string) $errorCode, ['0', '', 'false'], true)) {
            return self::hlrResult(false, self::formatHlrErrorMessage($apiMessage, $httpCode), [], $apiId, $body);
        }

        if (self::isAuthorizationFailure($data) || self::isErrorResponse($data)) {
            return self::hlrResult(false, self::formatHlrErrorMessage($apiMessage, $httpCode), [], $apiId, $body);
        }

        $payload = $data['DATA'] ?? $data['records'] ?? $data['data'] ?? null;
        if (is_array($payload) && $payload !== []) {
            $isList = array_key_exists(0, $payload);
            if ($isList && is_array($payload[0] ?? null)) {
                return self::hlrResult(true, 'Fatch Successfully', $payload[0], $apiId, $body);
            }
            if (!$isList) {
                return self::hlrResult(true, 'Fatch Successfully', $payload, $apiId, $body);
            }
        }

        $flat = [];
        foreach ($data as $key => $value) {
            if (is_scalar($value) && !in_array(strtolower((string) $key), ['error', 'status', 'message', 'msg'], true)) {
                $flat[$key] = $value;
            }
        }

        if ($flat !== []) {
            return self::hlrResult(true, 'Fatch Successfully', $flat, $apiId, $body);
        }

        // PlanConnect heavyRefresh: {"status":"Success","message":"...","data":{}}
        $status = strtolower((string) ($data['status'] ?? $data['Status'] ?? ''));
        if (in_array($status, ['success', 'ok', '1', 'true'], true)) {
            $okMessage = trim((string) ($apiMessage ?: ($data['message'] ?? $data['MESSAGE'] ?? 'Heavy Refresh Successfully Completed')));

            return self::hlrResult(true, $okMessage !== '' ? $okMessage : 'Heavy Refresh Successfully Completed', [], $apiId, $body);
        }

        return self::hlrResult(
            false,
            ($apiMessage !== null && $apiMessage !== '') ? $apiMessage : 'DTH customer details not found for this number.',
            [],
            $apiId,
            $body
        );
    }

    private static function hlrNonEmpty($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '' || strcasecmp($value, 'null') === 0) {
            return null;
        }

        return $value;
    }

    /**
     * Portal state id from the app's state_id, circle code, circle name, or the mobile number.
     */
    public static function resolvePortalStateId($raw, string $number = ''): int
    {
        $raw = trim((string) $raw);
        if ($raw !== '' && ctype_digit($raw)) {
            $id = (int) $raw;
            if ($id > 0 && DB::table('states')->where('id', $id)->exists()) {
                return $id;
            }
            $byCode = DB::table('states')
                ->where('plan_api_code', $raw)
                ->orWhere('plan_api_code', ltrim($raw, '0'))
                ->value('id');
            if ($byCode) {
                return (int) $byCode;
            }
        } elseif ($raw !== '') {
            $state = self::matchStateByCircleName($raw);
            if ($state) {
                return (int) $state->id;
            }
        }

        $digits = preg_replace('/\D/', '', $number) ?? '';
        if (strlen($digits) >= 10) {
            $lookup = self::fetchOperatorLookup(substr($digits, -10));
            if (! empty($lookup['ok'])) {
                $mapped = self::mapHlrToPortal(
                    isset($lookup['api_id']) ? (int) $lookup['api_id'] : null,
                    is_array($lookup['data'] ?? null) ? $lookup['data'] : []
                );
                if (! empty($mapped['state']->id)) {
                    return (int) $mapped['state']->id;
                }
            }
        }

        return 0;
    }

    /**
     * Website reads data as category → rows. The Android app also reads RDATA/records
     * and shows "Plans not available from API" when those keys are missing or data is a list.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public static function appPlanPayload(array $result): array
    {
        $data = $result['data'] ?? [];
        if (! is_array($data)) {
            $data = [];
        }
        if ($data !== [] && self::isListArray($data)) {
            $data = ['Plans' => $data];
        }

        $ok = ($result['type'] ?? '') === 'success' && $data !== [];

        return [
            'type' => $ok ? 'success' : 'error',
            'status' => $ok ? 'success' : 'error',
            'message' => $ok ? 'Fatch Successfully' : (string) ($result['message'] ?? 'No plans found for this operator and circle.'),
            'data' => $data,
            'RDATA' => $data,
            'records' => $data,
        ];
    }

    /**
     * Map PlanAPI OperatorFetch payload onto portal provider/state rows.
     * Prefer numeric codes, then Operator/Circle names from the documented response.
     *
     * @return array{provider: object|null, state: object|null}
     */
    public static function mapHlrToPortal(?int $apiId, array $data): array
    {
        $opCode = self::hlrNonEmpty($data['OpCode'] ?? null);
        $circleCode = self::hlrNonEmpty($data['CircleCode'] ?? null);
        $operatorName = self::hlrNonEmpty($data['Operator'] ?? $data['operator'] ?? null);
        $circleName = self::hlrNonEmpty($data['Circle'] ?? $data['circle'] ?? null);

        $provider = null;
        if ($apiId && $opCode !== null) {
            $provider = DB::table('api_provider_codes as c')
                ->join('providers as p', 'p.id', '=', 'c.provider_id')
                ->where('c.api_id', $apiId)
                ->where('c.provider_code', $opCode)
                ->where('p.service_id', 1)
                ->where('p.status', 1)
                ->select('p.*')
                ->first();
        }

        if (!$provider) {
            $provider = self::matchMobileProviderByName($operatorName);
        }

        $state = null;
        if ($circleCode !== null) {
            $state = DB::table('states')->where('plan_api_code', $circleCode)->first();
        }
        if (!$state && $circleName !== null) {
            $state = self::matchStateByCircleName($circleName);
        }

        return [
            'provider' => $provider,
            'state' => $state,
        ];
    }

    private static function matchMobileProviderByName(?string $name): ?object
    {
        if ($name === null || $name === '') {
            return null;
        }

        $needle = self::normalizeLookupName($name);
        $aliases = [
            'airtel' => 'airtel',
            'jio' => 'jio',
            'reliancejio' => 'jio',
            'ril' => 'jio',
            'vi' => 'vi',
            'vodafone' => 'vi',
            'idea' => 'vi',
            'vodafoneidea' => 'vi',
            'bsnl' => 'bsnl',
        ];
        $needle = $aliases[$needle] ?? $needle;

        $providers = DB::table('providers')->where('service_id', 1)->where('status', 1)->get();
        foreach ($providers as $provider) {
            $hay = self::normalizeLookupName((string) $provider->provider_name);
            if ($hay === $needle || str_contains($hay, $needle) || str_contains($needle, $hay)) {
                return $provider;
            }
        }

        return null;
    }

    private static function matchStateByCircleName(string $name): ?object
    {
        $needle = strtolower(trim($name));
        $states = DB::table('states')->get();
        foreach ($states as $state) {
            foreach (['state_name', 'mplan_state_code', 'plan_api_code'] as $field) {
                $value = strtolower(trim((string) ($state->{$field} ?? '')));
                if ($value !== '' && ($value === $needle || str_contains($value, $needle) || str_contains($needle, $value))) {
                    return $state;
                }
            }
        }

        return null;
    }

    private static function normalizeLookupName(string $name): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $name));
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
        if (!$result || empty($result['response'])) {
            return null;
        }

        $decoded = json_decode($result['response'], true);
        if (is_array($decoded) && self::isErrorResponse($decoded)) {
            return null;
        }

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

        // Primary/Backup Username|Password fields can override the API key (PlanConnect token).
        $api->resolved_api_key = trim((string) ($api->api_key ?? ''));
        if ($password !== null && $password !== '' && self::looksLikePlanApiKey($password)) {
            $api->resolved_api_key = $password;
        }
        if ($username !== null && $username !== '' && self::looksLikePlanApiKey($username)) {
            $api->resolved_api_key = $username;
        }
        if ($api->resolved_api_key === '' && $password !== null && $password !== '' && empty($api->api_key)) {
            $api->resolved_api_key = $password;
        }

        return $api;
    }

    /** Long token / UUID-style key from Plan/Circle settings (not short PlanAPI member id). */
    private static function looksLikePlanApiKey(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        if (self::looksLikeUuid($value)) {
            return true;
        }

        // Short numeric member IDs (PlanAPI) are not API keys.
        if (preg_match('/^\d{1,12}$/', $value)) {
            return false;
        }

        return strlen($value) >= 16;
    }

    public static function apiOptions()
    {
        if (!Schema::hasTable('apis')) {
            return collect();
        }

        $q = DB::table('apis')->orderBy('api_name');
        if (Schema::hasColumn('apis', 'api_type')) {
            $q->where(function ($w) {
                $w->whereNull('api_type')
                    ->orWhere('api_type', '')
                    ->orWhere('api_type', 'plan');
            });
        }

        return $q->get(['id', 'api_name', 'api_url', 'api_username', 'api_password', 'api_key']);
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
