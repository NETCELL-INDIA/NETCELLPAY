<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('providers')) {
            return;
        }

        Schema::table('providers', function (Blueprint $table) {
            if (!Schema::hasColumn('providers', 'backup_api_id')) {
                $table->unsignedBigInteger('backup_api_id')->default(0)->after('api_id');
            }
            if (!Schema::hasColumn('providers', 'backup_api2_id')) {
                $table->unsignedBigInteger('backup_api2_id')->default(0)->after('backup_api_id');
            }
            if (!Schema::hasColumn('providers', 'backup_api3_id')) {
                $table->unsignedBigInteger('backup_api3_id')->default(0)->after('backup_api2_id');
            }
            if (!Schema::hasColumn('providers', 'minium_amount')) {
                $table->decimal('minium_amount', 12, 2)->default(1)->after('backup_api3_id');
            }
            if (!Schema::hasColumn('providers', 'maxium_amount')) {
                $table->decimal('maxium_amount', 12, 2)->default(10000)->after('minium_amount');
            }
            if (!Schema::hasColumn('providers', 'provider_down')) {
                $table->tinyInteger('provider_down')->default(0)->after('maxium_amount');
            }
            if (!Schema::hasColumn('providers', 'amount_type')) {
                $table->string('amount_type', 50)->default('Commission Percent')->after('provider_down');
            }
            if (!Schema::hasColumn('providers', 'amount_value')) {
                $table->decimal('amount_value', 12, 2)->default(0)->after('amount_type');
            }
            if (!Schema::hasColumn('providers', 'provider_logo')) {
                $table->string('provider_logo', 255)->nullable()->after('amount_value');
            }
        });
    }

    public function down(): void
    {
        // keep columns
    }
};
