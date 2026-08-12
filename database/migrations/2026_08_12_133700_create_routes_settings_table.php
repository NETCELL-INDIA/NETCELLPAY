<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('routes_settings')) {
            Schema::create('routes_settings', function (Blueprint $table) {
                $table->id();
                $table->string('route_name', 100);
                $table->string('route_code', 100);
                $table->unsignedTinyInteger('priority')->default(1);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        $defaults = [
            ['route_name' => 'amount_wize', 'route_code' => 'amount_wize', 'priority' => 1, 'status' => 1],
            ['route_name' => 'user_wize', 'route_code' => 'user_wize', 'priority' => 2, 'status' => 1],
            ['route_name' => 'state_wize', 'route_code' => 'state_wize', 'priority' => 3, 'status' => 1],
            ['route_name' => 'provider', 'route_code' => 'provider', 'priority' => 4, 'status' => 1],
        ];

        foreach ($defaults as $row) {
            $exists = DB::table('routes_settings')->where('route_code', $row['route_code'])->exists();
            if (!$exists) {
                DB::table('routes_settings')->insert(array_merge($row, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        // keep table
    }
};
