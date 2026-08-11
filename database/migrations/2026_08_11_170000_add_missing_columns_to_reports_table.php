<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (!Schema::hasColumn('reports', 'path')) {
                $table->string('path', 32)->nullable();
            }
            if (!Schema::hasColumn('reports', 'ip_address')) {
                $table->string('ip_address', 45)->nullable();
            }
            if (!Schema::hasColumn('reports', 'admin_commission')) {
                $table->decimal('admin_commission', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('reports', 'api_commission')) {
                $table->decimal('api_commission', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('reports', 'callback_status')) {
                $table->unsignedTinyInteger('callback_status')->default(0);
            }
            if (!Schema::hasColumn('reports', 'api_operator_id')) {
                $table->string('api_operator_id')->nullable();
            }
            if (!Schema::hasColumn('reports', 'api_partner_order_id')) {
                $table->string('api_partner_order_id')->nullable();
            }
            if (!Schema::hasColumn('reports', 'complaint_id')) {
                $table->unsignedBigInteger('complaint_id')->default(0);
            }
            if (!Schema::hasColumn('reports', 'wt_commission')) {
                $table->decimal('wt_commission', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('reports', 'parent__Id')) {
                $table->unsignedBigInteger('parent__Id')->default(0);
            }
            if (!Schema::hasColumn('reports', 'retry_count')) {
                $table->integer('retry_count')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $columns = [
                'path',
                'ip_address',
                'admin_commission',
                'api_commission',
                'callback_status',
                'api_operator_id',
                'api_partner_order_id',
                'complaint_id',
                'wt_commission',
                'parent__Id',
                'retry_count',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('reports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
