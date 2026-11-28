<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_campaigns')) {
            Schema::table('notification_campaigns', function (Blueprint $table) {
                if (! Schema::hasColumn('notification_campaigns', 'campaign_name')) {
                    $table->string('campaign_name', 120)->nullable();
                }
                if (! Schema::hasColumn('notification_campaigns', 'short_message')) {
                    $table->string('short_message', 240)->nullable();
                }
                if (! Schema::hasColumn('notification_campaigns', 'body_html')) {
                    $table->text('body_html')->nullable();
                }
                if (! Schema::hasColumn('notification_campaigns', 'banner_url')) {
                    $table->string('banner_url', 500)->nullable();
                }
                if (! Schema::hasColumn('notification_campaigns', 'button_label')) {
                    $table->string('button_label', 40)->nullable();
                }
                if (! Schema::hasColumn('notification_campaigns', 'action_url')) {
                    $table->string('action_url', 500)->nullable();
                }
                if (! Schema::hasColumn('notification_campaigns', 'audience_filters')) {
                    $table->text('audience_filters')->nullable();
                }
            });
        }

        if (Schema::hasTable('messages') && ! Schema::hasColumn('messages', 'content_html')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->text('content_html')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notification_campaigns')) {
            Schema::table('notification_campaigns', function (Blueprint $table) {
                foreach (['audience_filters', 'action_url', 'button_label', 'banner_url', 'body_html', 'short_message', 'campaign_name'] as $column) {
                    if (Schema::hasColumn('notification_campaigns', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
        if (Schema::hasTable('messages') && Schema::hasColumn('messages', 'content_html')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropColumn('content_html');
            });
        }
    }
};
