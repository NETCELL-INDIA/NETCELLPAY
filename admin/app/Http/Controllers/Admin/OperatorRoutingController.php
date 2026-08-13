<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class OperatorRoutingController extends Controller
{
    private function ensureTable(): void
    {
        try {
            if (Schema::hasTable('operator_service_routings')) {
                return;
            }

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
        } catch (Throwable $e) {
            Log::warning('operator_service_routings table create failed: '.$e->getMessage());
        }
    }

    public function index()
    {
        $this->ensureTable();

        $services = Schema::hasTable('services')
            ? DB::table('services')->orderBy('service_name')->get(['id', 'service_name'])
            : collect();
        $apis = Schema::hasTable('apis')
            ? DB::table('apis')->orderBy('api_name')->get(['id', 'api_name'])
            : collect();

        $mobile = $services->first(function ($service) {
            return stripos((string) $service->service_name, 'mobile') !== false;
        });
        $defaultService = (int) ($mobile->id ?? optional($services->first())->id ?? 1);

        return view('admin.routings.operator-routing', compact('services', 'apis', 'defaultService'));
    }

    public function list(Request $request)
    {
        $this->ensureTable();

        $serviceId = (int) ($request->service_id ?: 1);
        if (!Schema::hasTable('providers')) {
            return response()->json(['type' => 'success', 'data' => []]);
        }

        $columns = ['id', 'provider_name', 'api_id'];
        foreach (['backup_api_id', 'backup_api2_id', 'backup_api3_id'] as $column) {
            if (Schema::hasColumn('providers', $column)) {
                $columns[] = $column;
            }
        }

        $providers = DB::table('providers')
            ->where('service_id', $serviceId)
            ->where(function ($q) {
                $q->where('status', 1)->orWhereNull('status');
            })
            ->orderBy('provider_name')
            ->get($columns);

        $saved = Schema::hasTable('operator_service_routings')
            ? DB::table('operator_service_routings')->where('service_id', $serviceId)->get()->keyBy('provider_id')
            : collect();

        $data = $providers->map(function ($p) use ($saved, $serviceId) {
            $row = $saved->get($p->id);

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
        $this->ensureTable();

        $serviceId = (int) $request->service_id;
        $rows = $request->rows;
        if (!$serviceId || !is_array($rows)) {
            return response()->json(['type' => 'error', 'message' => 'Invalid payload']);
        }
        if (!Schema::hasTable('operator_service_routings')) {
            return response()->json(['type' => 'error', 'message' => 'Routing table is not available. Run migrations on the server.']);
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

            $providerUpdate = [
                'api_id' => (int) ($row['primary_api_1'] ?? 0),
                'updated_at' => $now,
            ];
            foreach ([
                'backup_api_id' => (int) ($row['primary_api_2'] ?? 0),
                'backup_api2_id' => (int) ($row['primary_api_3'] ?? 0),
                'backup_api3_id' => (int) ($row['primary_api_4'] ?? 0),
            ] as $column => $value) {
                if (Schema::hasColumn('providers', $column)) {
                    $providerUpdate[$column] = $value;
                }
            }
            DB::table('providers')->where('id', $providerId)->update($providerUpdate);
        }

        return response()->json(['type' => 'success', 'message' => 'Operator API switch saved']);
    }
}
