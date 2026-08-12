<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlanInfoFetchService
{
    public const STOP_API_ID = 0;

    /** @var array<string, array{label: string, is_routing: bool, sort: int, default_primary: int|null, default_backup: int|null}> */
    public const SERVICES = [
        'hlr' => [
            'label' => 'Operator/Circle Fetch (HLR)',
            'is_routing' => false,
            'sort' => 1,
            'default_primary' => 7,
            'default_backup' => null,
        ],
        'roffer_airtel' => [
            'label' => 'Routing R-Offer Fetch (Airtel)',
            'is_routing' => true,
            'sort' => 2,
            'default_primary' => 6,
            'default_backup' => self::STOP_API_ID,
        ],
        'roffer_vi' => [
            'label' => 'Routing R-Offer Fetch (VI)',
            'is_routing' => true,
            'sort' => 3,
            'default_primary' => 6,
            'default_backup' => self::STOP_API_ID,
        ],
        'mobile_plan_retail' => [
            'label' => 'Mobile Plan/Roffer Fetch (Retail)',
            'is_routing' => false,
            'sort' => 4,
            'default_primary' => 6,
            'default_backup' => null,
        ],
        'dth_customer' => [
            'label' => 'DTH Customer Fetch',
            'is_routing' => false,
            'sort' => 5,
            'default_primary' => 6,
            'default_backup' => null,
        ],
        'dth_plan_list' => [
            'label' => 'DTH Plan List',
            'is_routing' => false,
            'sort' => 6,
            'default_primary' => 6,
            'default_backup' => null,
        ],
        'dth_heavy_refresh' => [
            'label' => 'DTH Heavy Refresh',
            'is_routing' => false,
            'sort' => 7,
            'default_primary' => 6,
            'default_backup' => null,
        ],
    ];

    public static function ensureTable(): void
    {
        if (Schema::hasTable('plan_info_fetch_settings')) {
            return;
        }

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
                $table->boolean('is_routing')->default(false);
                $table->unsignedTinyInteger('sort_order')->default(0);
                $table->timestamps();
            });

            self::seedDefaults();
        } catch (\Throwable $e) {
            // Fall back to legacy API ids when table cannot be created.
        }
    }

    public static function seedDefaults(): void
    {
        foreach (self::SERVICES as $key => $meta) {
            $exists = DB::table('plan_info_fetch_settings')->where('service_key', $key)->exists();
            if ($exists) {
                continue;
            }

            DB::table('plan_info_fetch_settings')->insert([
                'service_key' => $key,
                'service_label' => $meta['label'],
                'primary_api_id' => $meta['default_primary'],
                'backup_api_id' => $meta['default_backup'],
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

        $attempts = [
            ['api_id' => $setting->primary_api_id, 'username' => $setting->primary_username, 'password' => $setting->primary_password],
            ['api_id' => $setting->backup_api_id, 'username' => $setting->backup_username, 'password' => $setting->backup_password],
        ];

        foreach ($attempts as $attempt) {
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

            if (!$result || empty($result['response'])) {
                continue;
            }

            $decoded = json_decode($result['response'], true);
            if (is_array($decoded) && self::isErrorResponse($decoded)) {
                continue;
            }

            $result['api_id'] = (int) $api->id;

            return $result;
        }

        return null;
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

        $records = $data['records'] ?? $data['data'] ?? $data['Roffer'] ?? $data['Plans'] ?? [];

        return is_array($records) ? $records : [];
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

        foreach (['message', 'Message', 'error', 'Error', 'msg'] as $key) {
            if (!empty($data[$key]) && is_string($data[$key])) {
                return $data[$key];
            }
        }

        return null;
    }

    private static function isErrorResponse(array $data): bool
    {
        if (isset($data['status'])) {
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

        return false;
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
        $api->resolved_api_key = $password !== null && $password !== '' && empty($api->api_key)
            ? $password
            : ($api->api_key ?? '');

        if (!empty($username) && empty($api->api_key) && strlen($username) > 20) {
            $api->resolved_api_key = $username;
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
