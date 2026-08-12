<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('scheme_commissions')) {
            Schema::create('scheme_commissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('scheme_id')->index();
                $table->unsignedBigInteger('provider_id')->index();
                $table->string('wt_amount_type', 50)->nullable();
                $table->decimal('wt_amount_value', 12, 4)->default(0);
                $table->string('md_amount_type', 50)->nullable();
                $table->decimal('md_amount_value', 12, 4)->default(0);
                $table->string('dt_amount_type', 50)->nullable();
                $table->decimal('dt_amount_value', 12, 4)->default(0);
                $table->string('rt_amount_type', 50)->nullable();
                $table->decimal('rt_amount_value', 12, 4)->default(0);
                $table->timestamps();
                $table->unique(['scheme_id', 'provider_id']);
            });
        }

        if (Schema::hasTable('reports')) {
            Schema::table('reports', function (Blueprint $table) {
                if (!Schema::hasColumn('reports', 'dt_commission')) {
                    $table->decimal('dt_commission', 12, 4)->default(0)->after('commission');
                }
                if (!Schema::hasColumn('reports', 'md_commission')) {
                    $table->decimal('md_commission', 12, 4)->default(0)->after('dt_commission');
                }
            });
        }

        // Local schema restore: empty table causes rechargeCall to fail "commission not set".
        if (Schema::hasTable('scheme_commissions') && Schema::hasTable('providers')) {
            $now = now();
            $providerIds = DB::table('providers')->where('service_id', 1)->pluck('id');
            foreach ($providerIds as $providerId) {
                DB::table('scheme_commissions')->updateOrInsert(
                    ['scheme_id' => 1, 'provider_id' => $providerId],
                    [
                        'scheme_id' => 1,
                        'provider_id' => $providerId,
                        'wt_amount_type' => 'Commission Flat',
                        'wt_amount_value' => 0.5,
                        'md_amount_type' => 'Commission Flat',
                        'md_amount_value' => 0.5,
                        'dt_amount_type' => 'Commission Flat',
                        'dt_amount_value' => 0.5,
                        'rt_amount_type' => 'Commission Flat',
                        'rt_amount_value' => 0.5,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        // keep
    }
};
