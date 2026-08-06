<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('provider_name')->nullable();
            $table->string('api_name')->nullable();
            $table->text('api_url')->nullable();
            $table->text('api_key')->nullable();
            $table->text('api_username')->nullable();
            $table->text('api_password')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('status')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_settings');
    }
};
