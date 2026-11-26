<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_campaigns')) {
            Schema::create('notification_campaigns', function (Blueprint $table) {
                $table->id();
                $table->char('campaign_uuid', 36)->unique();
                $table->string('title', 120);
                $table->text('message');
                $table->string('category', 32)->index();
                $table->string('image_url', 500)->nullable();
                $table->string('deep_link', 500)->nullable();
                $table->string('priority', 16)->default('normal');
                $table->timestamp('expires_at')->nullable();
                $table->string('target_type', 16);
                $table->unsignedInteger('target_role_id')->nullable();
                $table->unsignedBigInteger('target_user_id')->nullable();
                $table->string('status', 32)->default('draft')->index();
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedInteger('total_recipients')->default(0);
                $table->unsignedInteger('success_count')->default(0);
                $table->unsignedInteger('failure_count')->default(0);
                $table->unsignedInteger('skipped_count')->default(0);
                $table->unsignedBigInteger('created_by')->index();
                $table->timestamps();
                $table->index(['status', 'scheduled_at'], 'notification_campaigns_status_scheduled_idx');
            });
        }

        if (! Schema::hasTable('notification_campaign_events')) {
            Schema::create('notification_campaign_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('campaign_id')->index();
                $table->unsignedBigInteger('actor_id');
                $table->string('action', 40);
                $table->string('note', 255)->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                if (! Schema::hasColumn('messages', 'campaign_id')) {
                    $table->unsignedBigInteger('campaign_id')->nullable()->index();
                }
                if (! Schema::hasColumn('messages', 'delivery_error')) {
                    $table->string('delivery_error', 255)->nullable();
                }
            });

            $unique = DB::selectOne("SHOW INDEX FROM `messages` WHERE Key_name = 'messages_campaign_recipient_unique'");
            if ($unique === null && Schema::hasColumn('messages', 'campaign_id')) {
                Schema::table('messages', function (Blueprint $table) {
                    $table->unique(['campaign_id', 'to_user_id'], 'messages_campaign_recipient_unique');
                });
            }
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'promotional_push_consent')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedTinyInteger('promotional_push_consent')->default(0);
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty. Rollback must not drop campaign tables,
        // new columns, or existing messages rows.
    }
};
