<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging — HTTP v1 preferred, legacy Server Key fallback.
 * Project must be netcellpay-fe31a (same as Android app).
 */
class FcmHttpV1Service
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const CACHE_KEY = 'fcm_v1_access_token';

    /** @var string|null Last send failure reason (for admin UI) */
    private static ?string $lastError = null;

    public static function lastError(): ?string
    {
        return self::$lastError;
    }

    public static function projectId(): string
    {
        $fromEnv = trim((string) config('services.fcm.project_id', ''));
        if ($fromEnv !== '') {
            return $fromEnv;
        }
        $sa = self::serviceAccount();
        $fromSa = is_array($sa) ? trim((string) ($sa['project_id'] ?? '')) : '';

        return $fromSa !== '' ? $fromSa : 'netcellpay-fe31a';
    }

    public static function channelId(): string
    {
        $id = trim((string) config('services.fcm.android_channel_id', 'high_importance_channel'));

        return $id !== '' ? $id : 'high_importance_channel';
    }

    public static function isConfigured(): bool
    {
        return self::isHttpV1Configured() || self::legacyServerKey() !== null;
    }

    public static function isHttpV1Configured(): bool
    {
        $sa = self::serviceAccount();

        return is_array($sa)
            && ! empty($sa['client_email'])
            && ! empty($sa['private_key']);
    }

    public static function send(string $deviceToken, string $title, string $body, array $data = [], int $userId = 0): bool
    {
        self::$lastError = null;
        $deviceToken = trim($deviceToken);
        if ($deviceToken === '') {
            self::$lastError = 'empty_token';

            return false;
        }

        if (self::isHttpV1Configured()) {
            return self::sendHttpV1($deviceToken, $title, $body, $data, $userId);
        }

        $legacyKey = self::legacyServerKey();
        if ($legacyKey) {
            return self::sendLegacy($deviceToken, $title, $body, $data, $userId, $legacyKey);
        }

        self::$lastError = 'Firebase not configured (upload service-account.json for netcellpay-fe31a)';

        return false;
    }

    private static function sendHttpV1(string $deviceToken, string $title, string $body, array $data, int $userId): bool
    {
        $accessToken = self::accessToken();
        if ($accessToken === null) {
            self::$lastError = 'FCM v1 OAuth failed (check service-account.json)';

            return false;
        }

        $payloadData = self::buildDataPayload($title, $body, $data);
        $channelId = self::channelId();
        $message = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'channel_id' => $channelId,
                        'sound' => 'default',
                        'default_sound' => true,
                    ],
                ],
                'data' => $payloadData,
            ],
        ];

        $url = 'https://fcm.googleapis.com/v1/projects/'.rawurlencode(self::projectId()).'/messages:send';
        $response = self::httpPostJson($url, $message, [
            'Authorization: Bearer '.$accessToken,
            'Content-Type: application/json; charset=UTF-8',
        ]);

        $status = (int) ($response['status'] ?? 0);
        $raw = (string) ($response['body'] ?? '');
        $decoded = json_decode($raw, true);

        if ($status >= 200 && $status < 300 && is_array($decoded) && isset($decoded['name'])) {
            return true;
        }

        $errorStatus = self::extractErrorStatus($decoded);
        self::$lastError = 'FCM v1 HTTP '.$status.($errorStatus !== '' ? ': '.$errorStatus : '');

        Log::warning('FCM v1 send failed', [
            'user_id' => $userId,
            'http_status' => $status,
            'error_status' => $errorStatus,
            'body' => self::safeLogBody($raw),
        ]);

        if ($userId > 0 && self::isInvalidDeviceToken($status, $errorStatus, $raw)) {
            self::clearDeviceToken($userId, $deviceToken);
        }

        if ($status === 401) {
            Cache::forget(self::CACHE_KEY);
        }

        return false;
    }

    private static function sendLegacy(string $deviceToken, string $title, string $body, array $data, int $userId, string $serverKey): bool
    {
        $channelId = self::channelId();
        $payloadData = self::buildDataPayload($title, $body, $data);
        $payload = [
            'to' => $deviceToken,
            'priority' => 'high',
            'content_available' => true,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'android_channel_id' => $channelId,
            ],
            'data' => $payloadData,
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'channel_id' => $channelId,
                    'sound' => 'default',
                    'default_sound' => true,
                    'default_vibrate_timings' => true,
                    'notification_priority' => 'PRIORITY_HIGH',
                ],
            ],
        ];

        $response = self::curl(
            'https://fcm.googleapis.com/fcm/send',
            json_encode($payload),
            [
                'Content-Type: application/json',
                'Authorization: key='.$serverKey,
            ]
        );

        $raw = (string) ($response['body'] ?? '');
        $decoded = json_decode($raw, true);

        if (is_array($decoded) && ((int) ($decoded['success'] ?? 0)) > 0) {
            return true;
        }
        if (is_array($decoded) && (isset($decoded['message_id']) || isset($decoded['name']))) {
            return true;
        }

        $fcmError = '';
        if (isset($decoded['results'][0]['error'])) {
            $fcmError = (string) $decoded['results'][0]['error'];
        } elseif (isset($decoded['error'])) {
            $fcmError = is_string($decoded['error']) ? $decoded['error'] : json_encode($decoded['error']);
        }
        self::$lastError = 'FCM legacy'.($fcmError !== '' ? ': '.$fcmError : ' send failed');

        Log::warning('FCM legacy send failed', [
            'user_id' => $userId,
            'error' => $fcmError,
            'body' => self::safeLogBody($raw),
        ]);

        if ($userId > 0 && $fcmError !== '') {
            foreach (['NotRegistered', 'UNREGISTERED', 'InvalidRegistration', 'MismatchSenderId'] as $needle) {
                if (stripos($fcmError, $needle) !== false) {
                    self::clearDeviceToken($userId, $deviceToken);
                    break;
                }
            }
        }

        return false;
    }

    private static function buildDataPayload(string $title, string $body, array $data): array
    {
        $payloadData = [
            'title' => $title,
            'body' => $body,
            'message' => $body,
            'subject' => $title,
        ];
        foreach ($data as $key => $value) {
            $payloadData[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        return $payloadData;
    }

    private static function extractErrorStatus(?array $decoded): string
    {
        if (! is_array($decoded) || ! isset($decoded['error'])) {
            return '';
        }
        $err = $decoded['error'];

        return is_array($err)
            ? (string) ($err['status'] ?? $err['message'] ?? '')
            : (string) $err;
    }

    private static function clearDeviceToken(int $userId, string $deviceToken): void
    {
        if (class_exists(\helpers::class) && method_exists(\helpers::class, 'clearUserPushToken')) {
            \helpers::clearUserPushToken($userId, $deviceToken);
        } elseif (class_exists(\App\Common::class) && method_exists(\App\Common::class, 'clearUserPushToken')) {
            \App\Common::clearUserPushToken($userId, $deviceToken);
        }
    }

    private static function legacyServerKey(): ?string
    {
        $key = trim((string) env('FCM_SERVER_KEY', ''));
        if ($key !== '' && str_starts_with($key, 'AAAA')) {
            return $key;
        }
        try {
            $key = trim((string) SystemSettingService::get('fcm_server_key', ''));
        } catch (\Throwable $e) {
            $key = '';
        }

        return ($key !== '' && str_starts_with($key, 'AAAA')) ? $key : null;
    }

    private static function isInvalidDeviceToken(int $httpStatus, string $errorStatus, string $raw): bool
    {
        $hay = $errorStatus.' '.$raw;
        foreach (['UNREGISTERED', 'NOT_FOUND', 'INVALID_ARGUMENT', 'SENDER_ID_MISMATCH'] as $needle) {
            if (stripos($hay, $needle) !== false) {
                return true;
            }
        }

        return $httpStatus === 404;
    }

    private static function accessToken(): ?string
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $sa = self::serviceAccount();
        if ($sa === null) {
            return null;
        }

        $jwt = self::makeJwt($sa);
        if ($jwt === null) {
            return null;
        }

        $response = self::httpPostForm(self::TOKEN_URL, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);
        $decoded = json_decode((string) ($response['body'] ?? ''), true);
        $token = is_array($decoded) ? trim((string) ($decoded['access_token'] ?? '')) : '';
        $expires = is_array($decoded) ? (int) ($decoded['expires_in'] ?? 3600) : 3600;

        if ($token === '') {
            Log::warning('FCM v1 OAuth token failed', [
                'http_status' => $response['status'] ?? 0,
                'body' => self::safeLogBody((string) ($response['body'] ?? '')),
            ]);

            return null;
        }

        Cache::put(self::CACHE_KEY, $token, max(60, $expires - 120));

        return $token;
    }

    private static function makeJwt(array $sa): ?string
    {
        $email = (string) ($sa['client_email'] ?? '');
        $privateKey = (string) ($sa['private_key'] ?? '');
        if ($email === '' || $privateKey === '') {
            return null;
        }

        $now = time();
        $header = self::b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = self::b64url(json_encode([
            'iss' => $email,
            'sub' => $email,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
            'scope' => self::SCOPE,
        ]));
        $unsigned = $header.'.'.$claims;
        $signature = '';
        $ok = openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (! $ok) {
            Log::warning('FCM v1 JWT sign failed');

            return null;
        }

        return $unsigned.'.'.self::b64url($signature);
    }

    private static function serviceAccount(): ?array
    {
        $path = (string) config('services.fcm.credentials');
        if ($path === '') {
            $path = storage_path('app/firebase/service-account.json');
        }
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);
        if (! is_array($json)) {
            Log::warning('FCM v1 service account JSON is invalid');

            return null;
        }

        // Prefer project_id from JSON when present
        if (! empty($json['project_id']) && empty(env('FIREBASE_PROJECT_ID'))) {
            // config already loaded; send() uses config project id — OK if .env set
        }

        return $json;
    }

    private static function b64url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function safeLogBody(string $body): string
    {
        $body = preg_replace('/-----BEGIN PRIVATE KEY-----.*?-----END PRIVATE KEY-----/s', '[redacted]', $body) ?? $body;
        $body = preg_replace('/"private_key"\s*:\s*"[^"]*"/', '"private_key":"[redacted]"', $body) ?? $body;

        return mb_substr($body, 0, 400);
    }

    private static function httpPostJson(string $url, array $payload, array $headers): array
    {
        return self::curl($url, json_encode($payload), $headers);
    }

    private static function httpPostForm(string $url, array $fields): array
    {
        return self::curl($url, http_build_query($fields), [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
    }

    private static function curl(string $url, string $body, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            Log::warning('FCM HTTP error', ['error' => $err, 'status' => $status]);

            return ['status' => $status ?: 0, 'body' => ''];
        }

        return ['status' => $status, 'body' => (string) $raw];
    }
}
