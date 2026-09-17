<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging HTTP v1 only (OAuth2 service account).
 * Never uses Legacy Server Key. Never exposes private keys in UI/logs.
 * Project must be netcellpay-fe31a (same as Android app).
 */
class FcmHttpV1Service
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const CACHE_KEY = 'fcm_v1_access_token';

    /** @var string|null Safe last failure reason (no secrets / no absolute paths) */
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
        return self::isHttpV1Configured();
    }

    public static function isHttpV1Configured(): bool
    {
        $sa = self::serviceAccount();

        return is_array($sa)
            && ! empty($sa['client_email'])
            && ! empty($sa['private_key']);
    }

    /**
     * Absolute path to credentials file (or null). Never log this in user-facing messages.
     */
    public static function credentialsPath(): ?string
    {
        $configured = trim((string) config('services.fcm.credentials', ''));
        if ($configured === '') {
            $configured = storage_path('app/firebase/service-account.json');
        }

        if (is_file($configured) && is_readable($configured)) {
            $real = realpath($configured);

            return $real !== false ? $real : $configured;
        }

        // Firebase Console downloads often use *-firebase-adminsdk-*.json
        $dir = storage_path('app/firebase');
        if (is_dir($dir)) {
            $candidates = glob($dir.DIRECTORY_SEPARATOR.'*.json') ?: [];
            usort($candidates, static function ($a, $b) {
                $aScore = str_ends_with(strtolower($a), 'service-account.json') ? 0 : (stripos($a, 'adminsdk') !== false ? 1 : 2);
                $bScore = str_ends_with(strtolower($b), 'service-account.json') ? 0 : (stripos($b, 'adminsdk') !== false ? 1 : 2);

                return $aScore <=> $bScore;
            });
            foreach ($candidates as $candidate) {
                if (is_file($candidate) && is_readable($candidate) && self::isCredentialsPathSafe($candidate)) {
                    $real = realpath($candidate);

                    return $real !== false ? $real : $candidate;
                }
            }
        }

        return $configured !== '' ? $configured : null;
    }

    /**
     * Reject credentials under web-accessible trees (public, public_html, etc.).
     */
    public static function isCredentialsPathSafe(?string $path = null): bool
    {
        $path = $path ?? self::credentialsPath();
        if ($path === null || $path === '') {
            return false;
        }
        $normalized = str_replace('\\', '/', strtolower($path));
        $parts = array_values(array_filter(explode('/', $normalized), static fn ($p) => $p !== '' && $p !== '.'));
        $banned = ['public', 'public_html', 'httpdocs', 'htdocs'];
        foreach ($parts as $part) {
            if (in_array($part, $banned, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{configured:bool,safe_path:bool,project_id:string,client_email_present:bool,message:string}
     */
    public static function status(): array
    {
        $path = self::credentialsPath();
        $safe = self::isCredentialsPathSafe($path);
        $exists = is_string($path) && is_file($path) && is_readable($path);
        $sa = ($exists && $safe) ? self::loadServiceAccountArray($path) : null;
        $configured = is_array($sa) && ! empty($sa['client_email']) && ! empty($sa['private_key']);

        if (! $exists) {
            $message = 'Service account JSON not found. Set FIREBASE_CREDENTIALS to a private path outside the web root.';
        } elseif (! $safe) {
            $message = 'Credentials path is under a web-accessible directory. Move JSON outside public_html/public.';
        } elseif (! $configured) {
            $message = 'Service account JSON is invalid (missing client_email/private_key).';
        } else {
            $message = 'Firebase HTTP v1 ready for project '.self::projectId().'.';
        }

        return [
            'configured' => $configured,
            'safe_path' => $safe,
            'project_id' => self::projectId(),
            'client_email_present' => is_array($sa) && ! empty($sa['client_email']),
            'message' => $message,
        ];
    }

    public static function send(string $deviceToken, string $title, string $body, array $data = [], int $userId = 0): bool
    {
        self::$lastError = null;
        $deviceToken = trim($deviceToken);
        if ($deviceToken === '') {
            self::$lastError = 'empty_token';

            return false;
        }

        if (! self::isHttpV1Configured()) {
            self::$lastError = self::status()['message'];

            return false;
        }

        return self::sendHttpV1($deviceToken, $title, $body, $data, $userId);
    }

    /** Attempt OAuth token fetch (no device send). Safe for deploy checks. */
    public static function verifyAuth(): bool
    {
        self::$lastError = null;
        if (! self::isHttpV1Configured()) {
            self::$lastError = self::status()['message'];

            return false;
        }
        $token = self::accessToken();
        if ($token === null) {
            self::$lastError = 'FCM v1 OAuth failed (check service account JSON and network)';

            return false;
        }

        return true;
    }

    private static function sendHttpV1(string $deviceToken, string $title, string $body, array $data, int $userId): bool
    {
        $accessToken = self::accessToken();
        if ($accessToken === null) {
            self::$lastError = 'FCM v1 OAuth failed (check service account JSON)';

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
        $path = self::credentialsPath();
        if ($path === null || $path === '') {
            return null;
        }
        if (! self::isCredentialsPathSafe($path)) {
            Log::warning('FCM credentials path rejected (web-accessible location)');

            return null;
        }
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        return self::loadServiceAccountArray($path);
    }

    private static function loadServiceAccountArray(string $path): ?array
    {
        $json = json_decode((string) file_get_contents($path), true);
        if (! is_array($json)) {
            Log::warning('FCM v1 service account JSON is invalid');

            return null;
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
        $body = preg_replace('/Bearer\s+[A-Za-z0-9._\-]+/', 'Bearer [redacted]', $body) ?? $body;

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
