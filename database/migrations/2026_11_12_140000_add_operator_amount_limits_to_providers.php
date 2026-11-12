<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('providers')) {
            return;
        }

        $addMin = ! Schema::hasColumn('providers', 'min_amount');
        $addMax = ! Schema::hasColumn('providers', 'max_amount');
        if ($addMin || $addMax) {
            Schema::table('providers', function (Blueprint $table) use ($addMin, $addMax) {
                if ($addMin) {
                    $table->decimal('min_amount', 12, 2)->default(1);
                }
                if ($addMax) {
                    $table->decimal('max_amount', 12, 2)->default(10000);
                }
            });
        }

        if ($addMin && Schema::hasColumn('providers', 'minium_amount')) {
            DB::statement('UPDATE providers SET min_amount = CASE WHEN minium_amount > 0 THEN minium_amount ELSE 1 END');
        }
        if ($addMax && Schema::hasColumn('providers', 'maxium_amount')) {
            DB::statement('UPDATE providers SET max_amount = CASE WHEN maxium_amount > 0 THEN maxium_amount ELSE 10000 END');
        }
        if (($addMin || $addMax) && Schema::hasColumn('providers', 'min_amount') && Schema::hasColumn('providers', 'max_amount')) {
            DB::statement('UPDATE providers SET max_amount = min_amount WHERE max_amount < min_amount');
        }
    }

    public function down(): void
    {
        // Keep the limits. Removing them would reject existing operator settings.
    }
};
