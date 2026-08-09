<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'visible_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('visible_password', 255)->nullable()->after('password');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'visible_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('visible_password');
            });
        }
    }
};
