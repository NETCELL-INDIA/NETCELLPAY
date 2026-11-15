<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (! Schema::hasColumn('reports', 'provider_req_id')) {
                $table->string('provider_req_id', 100)->nullable();
            }
            if (! Schema::hasColumn('reports', 'provider_txid')) {
                $table->string('provider_txid', 100)->nullable();
            }
            if (! Schema::hasColumn('reports', 'provider_recharge_id')) {
                $table->string('provider_recharge_id', 100)->nullable();
            }
        });

        Schema::table('apis', function (Blueprint $table) {
            if (! Schema::hasColumn('apis', 'recharge_id_value')) {
                $table->string('recharge_id_value', 64)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            foreach (['provider_req_id', 'provider_txid', 'provider_recharge_id'] as $column) {
                if (Schema::hasColumn('reports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('apis', function (Blueprint $table) {
            if (Schema::hasColumn('apis', 'recharge_id_value')) {
                $table->dropColumn('recharge_id_value');
            }
        });
    }
};
