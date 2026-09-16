<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['android_fcm_token', 'fcm_token', 'device_token'] as $column) {
            try {
                if (! Schema::hasColumn('users', $column)) {
                    DB::statement("ALTER TABLE `users` ADD COLUMN `{$column}` TEXT NULL");
                }
            } catch (\Throwable $e) {
            }
        }
    }

    public function down(): void
    {
        // Keep columns — safe for production rollback.
    }
};
