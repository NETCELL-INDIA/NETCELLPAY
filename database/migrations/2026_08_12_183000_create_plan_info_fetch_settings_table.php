<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('plan_info_fetch_settings')) {
            Schema::create('plan_info_fetch_settings', function (Blueprint $table) {
                $table->id();
                $table->string('service_key', 64)->unique();
                $table->string('service_label');
                $table->unsignedBigInteger('primary_api_id')->nullable();
                $table->string('primary_username', 255)->nullable();
                $table->string('primary_password', 255)->nullable();
                $table->unsignedBigInteger('backup_api_id')->nullable();
                $table->string('backup_username', 255)->nullable();
                $table->string('backup_password', 255)->nullable();
                $table->boolean('is_routing')->default(false);
                $table->unsignedTinyInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('plan_info_fetch_settings')) {
            return;
        }

        $defaults = [
            ['hlr', 'Operator/Circle Fetch (HLR)', 7, null, 1, 0],
            ['roffer_airtel', 'Routing R-Offer Fetch (Airtel)', 6, 0, 2, 1],
            ['roffer_vi', 'Routing R-Offer Fetch (VI)', 6, 0, 3, 1],
            ['mobile_plan_retail', 'Mobile Plan/Roffer Fetch (Retail)', 6, null, 4, 0],
            ['dth_customer', 'DTH Customer Fetch', 6, null, 5, 0],
            ['dth_plan_list', 'DTH Plan List', 6, null, 6, 0],
            ['dth_heavy_refresh', 'DTH Heavy Refresh', 6, null, 7, 0],
        ];

        $now = now();
        foreach ($defaults as [$key, $label, $primary, $backup, $sort, $routing]) {
            DB::table('plan_info_fetch_settings')->updateOrInsert(
                ['service_key' => $key],
                [
                    'service_label' => $label,
                    'primary_api_id' => $primary,
                    'backup_api_id' => $backup,
                    'is_routing' => (bool) $routing,
                    'sort_order' => $sort,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        // keep
    }
};
