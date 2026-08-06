<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('reports', function (Blueprint $table) {
            if (!Schema::hasColumn('reports', 'retry_count')) {
                $table->integer('retry_count')->default(0);
            }
        });
    }

    public function down()
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'retry_count')) {
                $table->dropColumn('retry_count');
            }
        });
    }
};
