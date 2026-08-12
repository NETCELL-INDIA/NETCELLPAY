<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reports') || !Schema::hasColumn('reports', 'status')) {
            return;
        }

        $column = DB::select("SHOW COLUMNS FROM reports WHERE Field = 'status'");
        $type = strtolower($column[0]->Type ?? '');

        // Production uses ENUM without "Processing"; normalize to varchar like local/dev.
        if (str_starts_with($type, 'enum')) {
            DB::statement("ALTER TABLE reports MODIFY status VARCHAR(50) NULL");
        }
    }

    public function down(): void
    {
        // keep
    }
};
