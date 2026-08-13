<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('website_media')) {
            return;
        }

        Schema::create('website_media', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);
            $table->string('title')->nullable();
            $table->string('image')->nullable();
            $table->string('link_url', 500)->nullable();
            $table->text('body')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('deleted_at')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_media');
    }
};
