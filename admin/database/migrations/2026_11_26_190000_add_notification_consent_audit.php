<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'promotional_push_consent_at')) {
                    $table->timestamp('promotional_push_consent_at')->nullable();
                }
                if (! Schema::hasColumn('users', 'promotional_push_consent_source')) {
                    $table->string('promotional_push_consent_source', 40)->nullable();
                }
            });
        }

        if (! Schema::hasTable('notification_consent_logs')) {
            Schema::create('notification_consent_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('channel', 20);
                $table->string('consent_type', 40);
                $table->unsignedTinyInteger('previous_value');
                $table->unsignedTinyInteger('new_value');
                $table->string('action', 20);
                $table->string('source', 40);
                $table->timestamp('created_at')->nullable();
                $table->index(['user_id', 'created_at'], 'notification_consent_logs_user_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_consent_logs');

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'promotional_push_consent_source')) {
                    $table->dropColumn('promotional_push_consent_source');
                }
                if (Schema::hasColumn('users', 'promotional_push_consent_at')) {
                    $table->dropColumn('promotional_push_consent_at');
                }
            });
        }
    }
};
