<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OperatorRoutingController extends Controller
{
    public function __construct()
    {
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        if (!Schema::hasTable('operator_service_routings')) {
            Schema::create('operator_service_routings', function ($table) {
                $table->id();
                $table->unsignedBigInteger('service_id');
                $table->unsignedBigInteger('provider_id');
                $table->unsignedTinyInteger('primary_orbit')->default(1);
                $table->decimal('min_roffer_pct', 8, 2)->default(0);
                $table->unsignedBigInteger('roffer_api_1')->nullable()->default(0);
                $table->unsignedBigInteger('roffer_api_2')->nullable()->default(0);
                $table->string('extra_params_1', 500)->nullable();
                $table->string('extra_params_2', 500)->nullable();
                $table->unsignedBigInteger('primary_api_1')->nullable()->default(0);
                $table->unsignedBigInteger('primary_api_2')->nullable()->default(0);
                $table->unsignedBigInteger('primary_api_3')->nullable()->default(0);
                $table->unsignedBigInteger('primary_api_4')->nullable()->default(0);
                $table->unsignedBigInteger('primary_api_5')->nullable()->default(0);
                $table->unsignedBigInteger('primary_api_6')->nullable()->default(0);
                $table->unsignedBigInteger('rehit_api_id')->nullable()->default(0);
                $table->unsignedBigInteger('pending_api_id')->nullable()->default(0);
                $table->string('routing_type', 50)->default('PendingCount');
                $table->timestamps();
                $table->unique(['service_id', 'provider_id']);
            });
        }

        // Ensure common mobile operators exist
        $mobileId = DB::table('services')->where('service_name', 'like', '%Mobile%')->value('id') ?: 1;
        $needed = ['Airtel', 'BSNL', 'Jio', 'Vi'];
        foreach ($needed as $name) {
            $exists = DB::table('providers')->where('provider_name', $name)->where('service_id', $mobileId)->exists();
            if (!$exists) {
                DB::table('providers')->insert([
                    'provider_name' => $name,
                    'service_id' => $mobileId,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function index()
    {
        $services = DB::table('services')->orderBy('service_name')->get(['id', 'service_name']);
        $apis = DB::table('apis')->orderBy('api_name')->get(['id', 'api_name']);
        $defaultService = $services->firstWhere('service_name', 'Mobile')->id
            ?? $services->first()->id
            ?? 1;

        return view('admin.routings.operator-routing', compact('services', 'apis', 'defaultService'));
    }

    public function list(Request $request)
    {
        $serviceId = (int) ($request->service_id ?: 1);
        $providers = DB::table('providers')
            ->where('service_id', $serviceId)
            ->where(function ($q) {
                $q->where('status', 1)->orWhereNull('status');
            })
            ->orderBy('provider_name')
            ->get(['id', 'provider_name', 'api_id', 'backup_api_id', 'backup_api2_id', 'backup_api3_id']);

        $saved = DB::table('operator_service_routings')
            ->where('service_id', $serviceId)
            ->get()
            ->keyBy('provider_id');

        $data = $providers->map(function ($p) use ($saved, $serviceId) {
            $row = $saved->get($p->id);
            // Prefer providers table (used by recharge) over routing-only values.
            return [
                'provider_id' => $p->id,
                'provider_name' => $p->provider_name,
                'service_id' => $serviceId,
                'primary_orbit' => (int) ($row->primary_orbit ?? 1),
                'min_roffer_pct' => (float) ($row->min_roffer_pct ?? 0),
                'roffer_api_1' => (int) ($row->roffer_api_1 ?? 0),
                'roffer_api_2' => (int) ($row->roffer_api_2 ?? 0),
                'extra_params_1' => $row->extra_params_1 ?? '',
                'extra_params_2' => $row->extra_params_2 ?? '',
                'primary_api_1' => (int) (($p->api_id ?? 0) ?: ($row->primary_api_1 ?? 0)),
                'primary_api_2' => (int) (($p->backup_api_id ?? 0) ?: ($row->primary_api_2 ?? 0)),
                'primary_api_3' => (int) (($p->backup_api2_id ?? 0) ?: ($row->primary_api_3 ?? 0)),
                'primary_api_4' => (int) (($p->backup_api3_id ?? 0) ?: ($row->primary_api_4 ?? 0)),
                'primary_api_5' => (int) ($row->primary_api_5 ?? 0),
                'primary_api_6' => (int) ($row->primary_api_6 ?? 0),
                'rehit_api_id' => (int) ($row->rehit_api_id ?? 0),
                'pending_api_id' => (int) ($row->pending_api_id ?? 0),
                'routing_type' => $row->routing_type ?? 'PendingCount',
            ];
        })->values();

        return response()->json(['type' => 'success', 'data' => $data]);
    }

    public function save(Request $request)
    {
        $serviceId = (int) $request->service_id;
        $rows = $request->rows;
        if (!$serviceId || !is_array($rows)) {
            return response()->json(['type' => 'error', 'message' => 'Invalid payload']);
        }

        $now = now();
        foreach ($rows as $row) {
            $providerId = (int) ($row['provider_id'] ?? 0);
            if ($providerId <= 0) {
                continue;
            }
            $payload = [
                'service_id' => $serviceId,
                'provider_id' => $providerId,
                'primary_orbit' => (int) ($row['primary_orbit'] ?? 1),
                'min_roffer_pct' => (float) ($row['min_roffer_pct'] ?? 0),
                'roffer_api_1' => (int) ($row['roffer_api_1'] ?? 0),
                'roffer_api_2' => (int) ($row['roffer_api_2'] ?? 0),
                'extra_params_1' => $row['extra_params_1'] ?? null,
                'extra_params_2' => $row['extra_params_2'] ?? null,
                'primary_api_1' => (int) ($row['primary_api_1'] ?? 0),
                'primary_api_2' => (int) ($row['primary_api_2'] ?? 0),
                'primary_api_3' => (int) ($row['primary_api_3'] ?? 0),
                'primary_api_4' => (int) ($row['primary_api_4'] ?? 0),
                'primary_api_5' => (int) ($row['primary_api_5'] ?? 0),
                'primary_api_6' => (int) ($row['primary_api_6'] ?? 0),
                'rehit_api_id' => (int) ($row['rehit_api_id'] ?? 0),
                'pending_api_id' => (int) ($row['pending_api_id'] ?? 0),
                'routing_type' => $row['routing_type'] ?? 'PendingCount',
                'updated_at' => $now,
            ];

            $exists = DB::table('operator_service_routings')
                ->where('service_id', $serviceId)
                ->where('provider_id', $providerId)
                ->first();

            if ($exists) {
                DB::table('operator_service_routings')->where('id', $exists->id)->update($payload);
            } else {
                $payload['created_at'] = $now;
                DB::table('operator_service_routings')->insert($payload);
            }

            // Sync into providers so recharge ProcessRecharge uses these APIs.
            DB::table('providers')->where('id', $providerId)->update([
                'api_id' => (int) ($row['primary_api_1'] ?? 0),
                'backup_api_id' => (int) ($row['primary_api_2'] ?? 0),
                'backup_api2_id' => (int) ($row['primary_api_3'] ?? 0),
                'backup_api3_id' => (int) ($row['primary_api_4'] ?? 0),
                'updated_at' => $now,
            ]);
        }

        return response()->json(['type' => 'success', 'message' => 'Operator API switch saved']);
    }
}
