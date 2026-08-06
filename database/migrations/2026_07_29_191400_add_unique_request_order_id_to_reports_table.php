<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reports', function (Blueprint $table) {
            // Add a unique index on request_order_id to avoid duplicate partner requests
            // MySQL allows multiple NULLs in a unique index, so only real values will be unique
            if (!Schema::hasColumn('reports', 'request_order_id')) {
                // If column does not exist, create it as nullable string
                $table->string('request_order_id')->nullable();
            }
            $table->unique('request_order_id', 'reports_request_order_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropUnique('reports_request_order_id_unique');
        });
    }
};
