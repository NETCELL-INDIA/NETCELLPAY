<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Session;

class GeneralRoutingsController extends Controller
{
    public function __construct()
    {
        $this->ensureTables();
    }

    private function ensureTables(): void
    {
        if (!Schema::hasTable('general_routings')) {
            Schema::create('general_routings', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->default(0);
                $table->unsignedBigInteger('provider_id')->default(0);
                $table->unsignedBigInteger('circle_id')->nullable()->default(0);
                $table->string('amounts', 255)->nullable();
                $table->string('primary_api_ids', 255)->nullable();
                $table->unsignedBigInteger('rehit_api_id')->nullable()->default(0);
                $table->unsignedBigInteger('pending_api_id')->nullable()->default(0);
                $table->string('routing_type', 50)->default('PendingCount');
                $table->unsignedTinyInteger('primary_rehit')->default(5);
                $table->unsignedTinyInteger('priority')->default(1);
                $table->string('status', 20)->default('Active');
                $table->string('only_user', 10)->default('No');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('states') && DB::table('states')->count() === 0) {
            $circles = [
                'Andhra Pradesh & Telangana', 'Assam', 'Bihar & Jharkhand', 'Delhi', 'Gujarat',
                'Himachal Pradesh', 'Haryana', 'Jammu and Kashmir', 'Kerala & Lakshadweep', 'Karnataka',
                'Kolkata', 'Maharashtra & Goa', 'Madhya Pradesh & Chhattisgarh', 'Mumbai', 'North East',
                'Odisha', 'Punjab', 'Rajasthan', 'Tamil Nadu', 'Uttar Pradesh (East)',
                'Uttar Pradesh (West)', 'West Bengal', 'OTHER',
            ];
            $now = Carbon::now();
            foreach ($circles as $name) {
                DB::table('states')->insert([
                    'state_name' => $name,
                    'status' => 1,
                ]);
            }
        }
    }

    public function index()
    {
        $providers = DB::table('providers')->where('status', 1)->orderBy('provider_name')->get(['id', 'provider_name', 'service_id']);
        $apis = DB::table('apis')->where('status', 1)->orderBy('api_name')->get(['id', 'api_name']);
        $circles = Schema::hasTable('states')
            ? DB::table('states')->where('status', 1)->orderBy('state_name')->get(['id', 'state_name'])
            : collect();

        return view('admin.routings.general-routings', compact('providers', 'apis', 'circles'));
    }

    public function list(Request $request)
    {
        $q = DB::table('general_routings as r')
            ->leftJoin('providers as p', 'p.id', '=', 'r.provider_id')
            ->leftJoin('states as s', 's.id', '=', 'r.circle_id')
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->select(
                'r.*',
                'p.provider_name',
                's.state_name as circle_name',
                'u.outlet_name',
                'u.first_name',
                'u.mobile_number'
            )
            ->orderBy('r.priority')
            ->orderByDesc('r.id');

        if ($request->provider_id) {
            $q->where('r.provider_id', (int) $request->provider_id);
        }
        if ($request->circle_id) {
            $q->where('r.circle_id', (int) $request->circle_id);
        }
        if ($request->user_id) {
            $q->where('r.user_id', (int) $request->user_id);
        }
        if ($request->api_id) {
            $apiId = (int) $request->api_id;
            $q->where(function ($w) use ($apiId) {
                $w->whereRaw('FIND_IN_SET(?, r.primary_api_ids)', [$apiId])
                    ->orWhere('r.rehit_api_id', $apiId)
                    ->orWhere('r.pending_api_id', $apiId);
            });
        }

        $rows = $q->limit(200)->get();
        $apiMap = DB::table('apis')->pluck('api_name', 'id');

        $data = $rows->map(function ($row) use ($apiMap) {
            $primaryNames = collect(explode(',', (string) $row->primary_api_ids))
                ->filter()
                ->map(fn ($id) => $apiMap[(int) $id] ?? ('API#' . $id))
                ->implode(', ');

            $userLabel = '-';
            if ($row->user_id) {
                $userLabel = $row->outlet_name ?: ($row->first_name ?: ('User #' . $row->user_id));
                if ($row->mobile_number) {
                    $userLabel .= ' (' . $row->mobile_number . ')';
                }
            }

            return [
                'id' => $row->id,
                'provider_id' => $row->provider_id,
                'provider_name' => $row->provider_name ?: 'All / Unknown',
                'circle_id' => $row->circle_id,
                'circle_name' => $row->circle_name ?: 'All Circles',
                'user_id' => $row->user_id,
                'user_name' => $userLabel,
                'amounts' => $row->amounts ?: '-',
                'primary_api_ids' => $row->primary_api_ids,
                'primary_apis' => $primaryNames ?: '-',
                'rehit_api_id' => $row->rehit_api_id,
                'pending_api_id' => $row->pending_api_id,
                'other_apis' => trim(
                    ($row->rehit_api_id ? 'Rehit: ' . ($apiMap[$row->rehit_api_id] ?? $row->rehit_api_id) : '') .
                    ($row->pending_api_id ? ' | Pending: ' . ($apiMap[$row->pending_api_id] ?? $row->pending_api_id) : '')
                ) ?: '-',
                'routing_type' => $row->routing_type,
                'primary_rehit' => $row->primary_rehit,
                'priority' => $row->priority,
                'status' => $row->status,
                'only_user' => $row->only_user,
            ];
        });

        return response()->json(['type' => 'success', 'data' => $data]);
    }

    public function save(Request $request)
    {
        $request->validate([
            'provider_id' => 'required|numeric',
            'primary_api_1' => 'required|numeric|min:1',
            'priority' => 'nullable|numeric|min:1|max:4',
            'status' => 'nullable|in:Active,Inactive',
        ]);

        $primary = collect([
            $request->primary_api_1,
            $request->primary_api_2,
            $request->primary_api_3,
            $request->primary_api_4,
            $request->primary_api_5,
            $request->primary_api_6,
        ])->filter(fn ($v) => (int) $v > 0)->values()->implode(',');

        $payload = [
            'user_id' => (int) ($request->user_id ?: 0),
            'provider_id' => (int) $request->provider_id,
            'circle_id' => (int) ($request->circle_id ?: 0),
            'amounts' => trim((string) $request->amounts),
            'primary_api_ids' => $primary,
            'rehit_api_id' => (int) ($request->rehit_api_id ?: 0),
            'pending_api_id' => (int) ($request->pending_api_id ?: 0),
            'routing_type' => $request->routing_type ?: 'PendingCount',
            'primary_rehit' => (int) ($request->primary_rehit ?? 5),
            'priority' => (int) ($request->priority ?: 1),
            'status' => $request->status ?: 'Active',
            'only_user' => $request->boolean('only_user') ? 'Yes' : 'No',
            'updated_at' => Carbon::now(),
        ];

        if ($request->id) {
            DB::table('general_routings')->where('id', (int) $request->id)->update($payload);
            return response()->json(['type' => 'success', 'message' => 'Routing updated successfully']);
        }

        $payload['created_at'] = Carbon::now();
        DB::table('general_routings')->insert($payload);

        return response()->json(['type' => 'success', 'message' => 'Routing added successfully']);
    }

    public function delete(Request $request)
    {
        $id = (int) $request->id;
        if (!$id) {
            return response()->json(['type' => 'error', 'message' => 'Invalid id']);
        }
        DB::table('general_routings')->where('id', $id)->delete();
        return response()->json(['type' => 'success', 'message' => 'Routing deleted']);
    }

    public function updateField(Request $request)
    {
        $id = (int) $request->id;
        if (!$id) {
            return response()->json(['type' => 'error', 'message' => 'Invalid id']);
        }

        $data = ['updated_at' => Carbon::now()];
        if ($request->filled('priority')) {
            $data['priority'] = (int) $request->priority;
        }
        if ($request->filled('status') && in_array($request->status, ['Active', 'Inactive'], true)) {
            $data['status'] = $request->status;
        }

        DB::table('general_routings')->where('id', $id)->update($data);
        return response()->json(['type' => 'success', 'message' => 'Updated']);
    }

    public function searchUsers(Request $request)
    {
        $term = trim((string) $request->q);
        $q = DB::table('users')->where('role_id', '!=', 1)->limit(20);
        if ($term !== '') {
            $q->where(function ($w) use ($term) {
                $w->where('outlet_name', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('mobile_number', 'like', "%{$term}%")
                    ->orWhere('id', $term);
            });
        }
        $users = $q->get(['id', 'outlet_name', 'first_name', 'mobile_number'])->map(function ($u) {
            $name = $u->outlet_name ?: ($u->first_name ?: 'User');
            return [
                'id' => $u->id,
                'text' => $u->id . ' - ' . $name . ($u->mobile_number ? ' (' . $u->mobile_number . ')' : ''),
            ];
        });

        return response()->json(['results' => $users]);
    }
}
