<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SmsApiService
{
    public static function ensureTable(): void
    {
        if (Schema::hasTable('sms_apis')) {
            return;
        }

        Schema::create('sms_apis', function ($table) {
            $table->id();
            $table->string('api_name');
            $table->string('api_method', 10)->default('GET');
            $table->text('request_url');
            $table->string('api_username', 255)->nullable();
            $table->string('api_password', 255)->nullable();
            $table->string('sender_id', 32)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('status')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        self::seedFromCompanySettings();
    }

    public static function seedFromCompanySettings(): void
    {
        if (DB::table('sms_apis')->exists()) {
            return;
        }

        $company = DB::table('companies')->where('status', 1)->orderBy('id')->first();
        if (!$company || empty($company->sms_request_url)) {
            return;
        }

        DB::table('sms_apis')->insert([
            'api_name' => 'Default SMS API',
            'api_method' => $company->sms_api_method ?: 'GET',
            'request_url' => $company->sms_request_url,
            'is_primary' => true,
            'status' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function all()
    {
        self::ensureTable();
        self::seedFromCompanySettings();

        return DB::table('sms_apis')->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id')->get();
    }

    public static function resolveForSend(): ?object
    {
        self::ensureTable();

        $api = DB::table('sms_apis')
            ->where('status', 1)
            ->where('is_primary', 1)
            ->whereNotNull('request_url')
            ->where('request_url', '!=', '')
            ->first();

        if ($api) {
            return $api;
        }

        return DB::table('sms_apis')
            ->where('status', 1)
            ->whereNotNull('request_url')
            ->where('request_url', '!=', '')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }

    public static function buildUrl(object $api, string $mobile, string $message, ?string $templateId = null): string
    {
        $url = (string) $api->request_url;
        $url = str_replace('{MOB}', $mobile, $url);
        $url = str_replace('{MSG}', urlencode($message), $url);
        $url = str_replace('{TMPID}', (string) ($templateId ?? ''), $url);
        $url = str_replace('{TMP_ID}', (string) ($templateId ?? ''), $url);
        $url = str_replace('{USER}', (string) ($api->api_username ?? ''), $url);
        $url = str_replace('{PASS}', (string) ($api->api_password ?? ''), $url);
        $url = str_replace('{SENDER}', (string) ($api->sender_id ?? ''), $url);

        return $url;
    }

    public static function setPrimary(int $id): void
    {
        self::ensureTable();
        DB::table('sms_apis')->update(['is_primary' => false]);
        DB::table('sms_apis')->where('id', $id)->update(['is_primary' => true, 'updated_at' => now()]);
    }
}
