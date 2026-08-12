<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('states')) {
            return;
        }

        Schema::table('states', function (Blueprint $table) {
            if (!Schema::hasColumn('states', 'mplan_state_code')) {
                $table->string('mplan_state_code', 100)->nullable()->after('state_name');
            }
            if (!Schema::hasColumn('states', 'plan_api_code')) {
                $table->string('plan_api_code', 50)->nullable()->after('mplan_state_code');
            }
        });

        $states = DB::table('states')->get(['id', 'state_name', 'mplan_state_code', 'plan_api_code']);
        foreach ($states as $state) {
            $updates = [];
            if (empty($state->mplan_state_code) && !empty($state->state_name)) {
                $updates['mplan_state_code'] = $state->state_name;
            }
            if (empty($state->plan_api_code) && !empty($state->state_name)) {
                $updates['plan_api_code'] = $state->state_name;
            }
            if ($updates !== []) {
                DB::table('states')->where('id', $state->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        // keep
    }
};
