<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MessageSettingService
{
    public const CHANNELS = ['whatsapp', 'email', 'push'];

    private const DEFAULTS = [
        ['slug' => 'otp', 'label' => 'Login OTP', 'whatsapp' => 1, 'email' => 1, 'push' => 0],
        ['slug' => 'create_user', 'label' => 'New User Credentials', 'whatsapp' => 1, 'email' => 1, 'push' => 0],
        ['slug' => 'forgot_password', 'label' => 'Forgot Password', 'whatsapp' => 1, 'email' => 1, 'push' => 0],
        ['slug' => 'forgot_pin', 'label' => 'Forgot PIN', 'whatsapp' => 1, 'email' => 0, 'push' => 0],
        ['slug' => 'fund_receive', 'label' => 'Fund Credit / Receive', 'whatsapp' => 1, 'email' => 0, 'push' => 1],
        ['slug' => 'fund_reverse', 'label' => 'Fund Debit / Reverse', 'whatsapp' => 1, 'email' => 0, 'push' => 0],
        ['slug' => 'fund_transfer', 'label' => 'Fund Transfer Request', 'whatsapp' => 1, 'email' => 0, 'push' => 0],
        ['slug' => 'recharge_success', 'label' => 'Recharge Success', 'whatsapp' => 0, 'email' => 0, 'push' => 1],
        ['slug' => 'recharge_refund', 'label' => 'Recharge Refund', 'whatsapp' => 1, 'email' => 0, 'push' => 1],
        ['slug' => 'admin_notification', 'label' => 'Admin Broadcast Notification', 'whatsapp' => 0, 'email' => 0, 'push' => 1],
    ];

    public static function ensureTable(): void
    {
        try {
            if (! Schema::hasTable('message_settings')) {
                Schema::create('message_settings', function ($table) {
                    $table->id();
                    $table->string('slug', 120)->unique();
                    $table->string('label', 190);
                    $table->unsignedTinyInteger('whatsapp_enabled')->default(1);
                    $table->unsignedTinyInteger('email_enabled')->default(1);
                    $table->unsignedTinyInteger('push_enabled')->default(1);
                    $table->unsignedInteger('sort_order')->default(0);
                    $table->timestamps();
                });
            }
            self::seedDefaults();
        } catch (\Throwable $e) {
        }
    }

    public static function seedDefaults(): void
    {
        if (! Schema::hasTable('message_settings')) {
            return;
        }
        $order = 0;
        foreach (self::DEFAULTS as $row) {
            $exists = DB::table('message_settings')->where('slug', $row['slug'])->exists();
            if ($exists) {
                continue;
            }
            DB::table('message_settings')->insert([
                'slug' => $row['slug'],
                'label' => $row['label'],
                'whatsapp_enabled' => (int) $row['whatsapp'],
                'email_enabled' => (int) $row['email'],
                'push_enabled' => (int) $row['push'],
                'sort_order' => ++$order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $extraSlugs = collect();
        foreach (['whatsapp_templates', 'sms_templates', 'email_templates'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach (DB::table($table)->distinct()->pluck('slug') as $slug) {
                $slug = trim((string) $slug);
                if ($slug !== '') {
                    $extraSlugs->push($slug);
                }
            }
        }
        $maxOrder = (int) DB::table('message_settings')->max('sort_order');
        foreach ($extraSlugs->unique() as $slug) {
            if (DB::table('message_settings')->where('slug', $slug)->exists()) {
                continue;
            }
            DB::table('message_settings')->insert([
                'slug' => $slug,
                'label' => ucwords(str_replace('_', ' ', $slug)),
                'whatsapp_enabled' => 1,
                'email_enabled' => 1,
                'push_enabled' => 0,
                'sort_order' => ++$maxOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public static function all(): array
    {
        self::ensureTable();

        return DB::table('message_settings')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public static function channelEnabled(string $slug, string $channel): bool
    {
        $slug = trim($slug);
        if ($slug === '') {
            return true;
        }
        self::ensureTable();
        if (! in_array($channel, self::CHANNELS, true)) {
            return true;
        }

        $col = $channel.'_enabled';
        $row = DB::table('message_settings')->where('slug', $slug)->first([$col]);
        if (! $row) {
            return true;
        }

        return (int) ($row->{$col} ?? 1) === 1;
    }

    public static function emailGloballyEnabled(): bool
    {
        try {
            $company = DB::table('companies')->where('id', 1)->first(['email_message']);

            return $company && (int) ($company->email_message ?? 0) === 1;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function saveSettings(array $rows, bool $emailGlobal): void
    {
        self::ensureTable();
        DB::table('companies')->where('id', 1)->update([
            'email_message' => $emailGlobal ? 1 : 0,
            'updated_at' => now(),
        ]);

        foreach ($rows as $row) {
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }
            $payload = [
                'whatsapp_enabled' => ! empty($row['whatsapp']) ? 1 : 0,
                'email_enabled' => ! empty($row['email']) ? 1 : 0,
                'push_enabled' => ! empty($row['push']) ? 1 : 0,
                'updated_at' => now(),
            ];
            DB::table('message_settings')->where('slug', $slug)->update($payload);

            if (Schema::hasTable('whatsapp_templates')) {
                DB::table('whatsapp_templates')->where('slug', $slug)->update(['status' => $payload['whatsapp_enabled']]);
            }
            if (Schema::hasTable('sms_templates')) {
                DB::table('sms_templates')->where('slug', $slug)->update(['status' => $payload['whatsapp_enabled']]);
            }
            if (Schema::hasTable('email_templates')) {
                DB::table('email_templates')->where('slug', $slug)->update(['status' => $payload['email_enabled']]);
            }
        }
    }
}
