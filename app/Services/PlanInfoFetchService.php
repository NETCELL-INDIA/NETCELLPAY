<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $circle = trim((string) ($state->mplan_state_code ?? $state->state_name ?? ''));
        if (!$state || $circle === '') {
            return self::planFetchResult(false, 'error', 'Invalid state selected. Circle/plan code is not configured.');
        }

        $circle = str_replace(' ', '%20', $circle);
        $serviceKey = self::planServiceKey($providerId);

        $setting = Schema::hasTable('plan_info_fetch_settings')
            ? DB::table('plan_info_fetch_settings')->where('service_key', $serviceKey)->first()
            : null;

        $attempts = $setting
            ? [
                ['api_id' => $setting->primary_api_id, 'username' => $setting->primary_username, 'password' => $setting->primary_password],
                ['api_id' => $setting->backup_api_id, 'username' => $setting->backup_username, 'password' => $setting->backup_password],
            ]
            : [
                ['api_id' => 6, 'username' => null, 'password' => null],
            ];

        $lastResult = null;

        foreach ($attempts as $index => $attempt) {
            $api = self::resolveApiRow($attempt['api_id'], $attempt['username'], $attempt['password']);
            if (!$api) {
                continue;
            }

            $operatorCode = \helpers::PlanProviderCode($api->id, $providerId);
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

            $useEnvOverride = $index === 0;
            $url = self::buildMobilePlansUrl($api, (string) $operatorCode, $circle, $useEnvOverride);
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
            $attemptLabel = $index === 0 ? 'primary' : 'backup';
            $requestResult = self::executePlanApiRequest($url, 'Plans', $orderId, [
                'operator' => (string) $operatorCode,
                'circle' => urldecode($circle),
                'provider_id' => $providerId,
                'state_id' => $stateId,
                'api_id' => (int) $api->id,
                'attempt' => $attemptLabel,
            ]);

            $lastResult = $requestResult;

            if ($requestResult['ok']) {
                return $requestResult;
            }

            if ($index === 0 && count($attempts) > 1 && self::shouldRetryWithBackupApi($attempts[1]['api_id'] ?? null)) {
                Log::info('Plan API primary failed, trying backup', [
                    'service_key' => $serviceKey,
                    'primary_api_id' => (int) $api->id,
                    'backup_api_id' => self::nullableApiId($attempts[1]['api_id'] ?? null),
                    'http_code' => $requestResult['http_code'],
                    'message' => $requestResult['message'],
                ]);
                continue;
            }

            break;
        }

        if ($lastResult !== null) {
            return $lastResult;
        }

        return self::planFetchResult(
            false,
            'error',
            'Unable to fetch plans. Check plan API settings and operator code.'
        );
    }

    public static function buildMobilePlansUrl(object $api, string $operatorCode, string $circleCode, bool $useEnvOverride = true): ?string
    {
        $key = self::resolvePlanApiKey($api, $useEnvOverride);
        if ($key === null || $key === '') {
            return null;
        }

        $base = rtrim(self::resolvePlanApiBaseUrl($api, $useEnvOverride), '/');

        return $base . '/plans.php?apikey=' . urlencode($key)
            . '&operator=' . urlencode($operatorCode)
            . '&cricle=' . $circleCode;
    }

    public static function resolvePlanApiBaseUrl(object $api, bool $useEnvOverride = true): string
    {
        if ($useEnvOverride) {
            $envBase = trim((string) config('plan_api.base_url', ''));
            if ($envBase !== '') {
                return $envBase;
            }
        }

        return rtrim((string) ($api->api_url ?? ''), '/');
    }

    public static function resolvePlanApiKey(object $api, bool $useEnvOverride = true): ?string
    {
        if ($useEnvOverride) {
            $envKey = trim((string) config('plan_api.api_key', ''));
            if ($envKey !== '') {
                return $envKey;
            }
        }

        $key = $api->resolved_api_key ?? $api->api_key ?? null;

        return ($key === null || $key === '') ? null : (string) $key;
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
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, (int) config('plan_api.connect_timeout', 10));
        curl_setopt($curl, CURLOPT_TIMEOUT, (int) config('plan_api.timeout', 30));
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');

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
            || str_contains($message, 'invalid api key')
            || str_contains($message, 'invalid apikey');
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
        if (!$setting) {
            return self::legacyFetch($serviceKey, $urlBuilder, $modal, $orderPrefix);
        }

        $attempts = [
            ['api_id' => $setting->primary_api_id, 'username' => $setting->primary_username, 'password' => $setting->primary_password],
            ['api_id' => $setting->backup_api_id, 'username' => $setting->backup_username, 'password' => $setting->backup_password],
        ];

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
            $result = \helpers::curl($url, 'GET', '', [], 'yes', $modal, $orderId);

            if ($result && self::isSuccessfulFetchResponse($result)) {
                $result['api_id'] = (int) $api->id;

                return $result;
            }

            if ($index === 0 && self::shouldRetryWithBackupApi($attempts[1]['api_id'] ?? null)) {
                Log::info('Plan info fetch primary failed, trying backup', [
                    'service_key' => $serviceKey,
                    'primary_api_id' => (int) $api->id,
                    'backup_api_id' => self::nullableApiId($attempts[1]['api_id'] ?? null),
                    'http_code' => (int) ($result['code'] ?? 0),
                    'endpoint' => isset($url) ? \helpers::redactUrlSecrets($url) : null,
                ]);
            }
        }

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
