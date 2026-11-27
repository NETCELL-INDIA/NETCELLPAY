<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotificationConsentService
{
    public const TYPE = 'promotional_push';

    public function ready(): bool
    {
        return Schema::hasTable('users')
            && Schema::hasColumn('users', 'promotional_push_consent')
            && Schema::hasColumn('users', 'promotional_push_consent_at')
            && Schema::hasTable('notification_consent_logs');
    }

    public function current(int $userId): array
    {
        $row = DB::table('users')->where('id', $userId)->first([
            'promotional_push_consent',
            'promotional_push_consent_at',
        ]);

        return [
            'promotional_push_consent' => (int) ($row->promotional_push_consent ?? 0) === 1,
            'promotional_push_consent_at' => $row->promotional_push_consent_at ?? null,
        ];
    }

    public function update(int $userId, bool $consent, string $source): array
    {
        $current = (int) DB::table('users')->where('id', $userId)->value('promotional_push_consent');
        $next = $consent ? 1 : 0;
        if ($current === $next) {
            return $this->current($userId);
        }

        $now = Carbon::now();
        DB::table('users')->where('id', $userId)->update([
            'promotional_push_consent' => $next,
            'promotional_push_consent_at' => $now,
            'promotional_push_consent_source' => mb_substr($source, 0, 40),
            'updated_at' => $now,
        ]);
        DB::table('notification_consent_logs')->insert([
            'user_id' => $userId,
            'channel' => 'push',
            'consent_type' => self::TYPE,
            'previous_value' => $current,
            'new_value' => $next,
            'action' => $next === 1 ? 'granted' : 'revoked',
            'source' => mb_substr($source, 0, 40),
            'created_at' => $now,
        ]);

        return $this->current($userId);
    }
}
