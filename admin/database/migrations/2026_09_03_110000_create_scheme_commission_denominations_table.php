<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scheme_commission_denominations')) {
            return;
        }

        Schema::create('scheme_commission_denominations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scheme_id')->index();
            $table->unsignedBigInteger('provider_id')->index();
            $table->decimal('min_amount', 12, 2)->default(0);
            $table->decimal('max_amount', 12, 2)->default(0);
            $table->string('md_amount_type', 50)->nullable();
            $table->decimal('md_amount_value', 12, 4)->default(0);
            $table->string('dt_amount_type', 50)->nullable();
            $table->decimal('dt_amount_value', 12, 4)->default(0);
            $table->string('rt_amount_type', 50)->nullable();
            $table->decimal('rt_amount_value', 12, 4)->default(0);
            $table->string('ap_amount_type', 50)->nullable();
            $table->decimal('ap_amount_value', 12, 4)->default(0);
            $table->timestamps();
            $table->index(['scheme_id', 'provider_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheme_commission_denominations');
    }
};
