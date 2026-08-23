<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OperatorRoutingController extends Controller
{
    private const API_COLUMNS = [
        'api_id',
        'backup_api_id',
        'backup_api2_id',
        'backup_api3_id',
    ];

    public function __construct()
    {
        $this->ensureUserDownTable();
        $this->ensureOperatorTypeColumn();
    }

    private function ensureOperatorTypeColumn(): void
    {
        if (!Schema::hasColumn('providers', 'operator_type')) {
            Schema::table('providers', function ($table) {
                $table->string('operator_type', 100)->nullable()->after('provider_name');
            });
        }
    }

    private function ensureUserDownTable(): void
    {
        if (!Schema::hasTable('provider_user_downs')) {
            Schema::create('provider_user_downs', function ($table) {
                $table->id();
                $table->unsignedBigInteger('provider_id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedTinyInteger('status')->default(1);
                $table->timestamps();
                $table->unique(['provider_id', 'user_id']);
            });
        }
    }

    private function operatorsQuery()
    {
        return DB::table('providers')
            ->where(function ($query) {
                $query->whereNull('providers.deleted_at')->orWhere('providers.deleted_at', '!=', 1);
            });
    }

    private function rechargeApisQuery()
    {
        $query = DB::table('apis')
            ->where('apis.status', 1)
            ->where(function ($q) {
                $q->whereNull('apis.deleted_at')->orWhere('apis.deleted_at', '!=', 1);
            });

        if (Schema::hasColumn('apis', 'api_type')) {
            $query->whereRaw("LOWER(COALESCE(NULLIF(apis.api_type, ''), 'recharge')) = 'recharge'");
        }

        return $query;
    }

    private function providerApiColumns(): array
    {
        return array_values(array_filter(self::API_COLUMNS, function ($column) {
            return Schema::hasColumn('providers', $column);
        }));
    }

    private function defaultLogoUrl(): string
    {
        return asset('assets/images/users/user-dummy-img.jpg');
    }

    private function logoUrl($logo): string
    {
        $logo = basename((string) $logo);
        if ($logo !== '' && is_file(public_path('provider_logo/' . $logo))) {
            return asset('provider_logo/' . $logo);
        }
        if ($logo !== '' && is_file(public_path('bank_logo/' . $logo))) {
            return asset('bank_logo/' . $logo);
        }

        return $this->defaultLogoUrl();
    }

    private function deleteLogoFile($logo): void
    {
        $logo = basename((string) $logo);
        if ($logo === '') {
            return;
        }

        foreach (['provider_logo', 'bank_logo'] as $directory) {
            $path = public_path($directory . '/' . $logo);
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function validateApiIds(array $apiIds): bool
    {
        $requestedApiIds = array_values(array_unique(array_filter($apiIds, function ($apiId) {
            return (int) $apiId > 0;
        })));

        if (empty($requestedApiIds)) {
            return true;
        }

        return $this->rechargeApisQuery()->whereIn('apis.id', $requestedApiIds)->count() === count($requestedApiIds);
    }

    private function syncRoutingTable(int $serviceId, int $operatorId, array $apiIds): void
    {
        if (!Schema::hasTable('operator_service_routings')) {
            return;
        }

        $routingKeys = [
            'service_id' => $serviceId,
            'provider_id' => $operatorId,
        ];
        $routingPayload = [
            'primary_api_1' => $apiIds['api_id'],
            'primary_api_2' => $apiIds['backup_api_id'],
            'primary_api_3' => $apiIds['backup_api2_id'],
            'primary_api_4' => $apiIds['backup_api3_id'],
            'updated_at' => now(),
        ];
        $routing = DB::table('operator_service_routings')->where($routingKeys)->first();

        if ($routing) {
            DB::table('operator_service_routings')->where('id', $routing->id)->update($routingPayload);
        } else {
            $routingPayload['created_at'] = now();
            DB::table('operator_service_routings')->insert(array_merge($routingKeys, $routingPayload));
        }
    }

    public function index()
    {
        $services = Schema::hasTable('services')
            ? DB::table('services')
                ->where('services.status', 1)
                ->where(function ($q) {
                    $q->whereNull('services.deleted_at')->orWhere('services.deleted_at', '!=', 1);
                })
                ->orderBy('services.service_name')
                ->get(['services.id', 'services.service_name'])
            : collect();

        return view('admin.routings.operator-routing', compact('services'));
    }

    public function list(Request $request)
    {
        $query = $this->operatorsQuery()
            ->leftJoin('services', 'services.id', '=', 'providers.service_id');

        if ($request->filled('operator_name')) {
            $query->where('providers.provider_name', 'like', '%' . trim((string) $request->operator_name) . '%');
        }

        if ($request->filled('operator_type')) {
            $operatorType = trim((string) $request->operator_type);
            if (is_numeric($operatorType)) {
                $query->where('providers.service_id', (int) $operatorType);
            } else {
                $query->where('services.service_name', 'like', '%' . $operatorType . '%');
            }
        }

        $rows = $query->orderBy('providers.provider_name')
            ->get([
                'providers.id as operator_id',
                'providers.provider_name as operator_name',
                'services.service_name as operator_type',
                'providers.service_id',
                'providers.provider_logo',
                'providers.status',
            ])
            ->map(function ($row) {
                $row->logo_url = $this->logoUrl($row->provider_logo ?? '');
                $row->user_down_count = DB::table('provider_user_downs')
                    ->where('provider_id', $row->operator_id)
                    ->where('status', 1)
                    ->count();
                return $row;
            });

        return response()->json([
            'type' => 'success',
            'data' => $rows,
        ]);
    }

    public function save(Request $request)
    {
        $request->validate([
            'operator_id' => 'nullable|numeric|min:0',
            'operator_name' => 'required|string|max:255',
            'operator_type' => 'required_without:service_id|numeric|min:1',
            'service_id' => 'nullable|numeric|min:1',
            'api_id' => 'nullable|numeric|min:0',
            'backup_api_id' => 'nullable|numeric|min:0',
            'backup_api2_id' => 'nullable|numeric|min:0',
            'backup_api3_id' => 'nullable|numeric|min:0',
            'status' => 'required|in:0,1',
            'logo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_logo' => 'nullable|boolean',
        ]);

        $operatorId = (int) ($request->operator_id ?: 0);
        $serviceId = (int) ($request->operator_type ?: $request->service_id);
        $operator = null;

        if ($operatorId > 0) {
            $operator = $this->operatorsQuery()->where('providers.id', $operatorId)->first();
            if (!$operator) {
                return response()->json(['type' => 'error', 'message' => 'Operator not found.']);
            }
        }

        $hasApiFields = $request->hasAny(self::API_COLUMNS);
        $apiIds = [];
        foreach (self::API_COLUMNS as $column) {
            $apiIds[$column] = ($operator && !$hasApiFields)
                ? (int) ($operator->{$column} ?? 0)
                : (int) ($request->{$column} ?: 0);
        }

        $service = DB::table('services')
            ->where('id', $serviceId)
            ->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('deleted_at')->orWhere('deleted_at', '!=', 1);
            })
            ->first(['id', 'service_name']);

        if (!$service) {
            return response()->json(['type' => 'error', 'message' => 'Please select a valid service.']);
        }

        if ((!$operator || $hasApiFields) && !$this->validateApiIds($apiIds)) {
            return response()->json(['type' => 'error', 'message' => 'Please select valid recharge APIs.']);
        }

        $logoName = $operator->provider_logo ?? '';
        if ($request->hasFile('logo')) {
            $logoDirectory = public_path('provider_logo');
            if (!is_dir($logoDirectory)) {
                mkdir($logoDirectory, 0755, true);
            }

            $logoName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $request->file('logo')->extension();
            $request->file('logo')->move($logoDirectory, $logoName);

            if ($operator && !empty($operator->provider_logo)) {
                $this->deleteLogoFile($operator->provider_logo);
            }
        } elseif ($request->boolean('remove_logo')) {
            if ($operator && !empty($operator->provider_logo)) {
                $this->deleteLogoFile($operator->provider_logo);
            }
            $logoName = '';
        }

        $payload = [
            'provider_name' => trim((string) $request->operator_name),
            'operator_type' => $service->service_name,
            'service_id' => $serviceId,
            'status' => (int) $request->status,
            'provider_logo' => $logoName,
            'updated_at' => now(),
        ];

        if (!$operator || $hasApiFields) {
            foreach ($apiIds as $column => $apiId) {
                if (Schema::hasColumn('providers', $column)) {
                    $payload[$column] = $apiId;
                }
            }
        }

        if ($operator) {
            DB::table('providers')->where('id', $operatorId)->update($payload);
            $message = 'Operator updated successfully.';
        } else {
            $payload += [
                'minium_amount' => 1,
                'maxium_amount' => 100000,
                'provider_down' => 0,
                'amount_type' => 'Commission Percent',
                'amount_value' => 0,
                'created_at' => now(),
            ];
            $operatorId = DB::table('providers')->insertGetId($payload);
            $message = 'Operator added successfully.';
        }

        if (!$operator || $hasApiFields) {
            $this->syncRoutingTable($serviceId, $operatorId, $apiIds);
        }

        return response()->json([
            'type' => 'success',
            'message' => $message,
        ]);
    }

    public function apiSwitching()
    {
        $operators = $this->operatorsQuery()
            ->leftJoin('services', 'services.id', '=', 'providers.service_id')
            ->orderBy('providers.provider_name')
            ->get(['providers.id', 'providers.provider_name', DB::raw('COALESCE(services.service_name, providers.operator_type) as operator_type')]);

        $apis = $this->rechargeApisQuery()
            ->orderBy('apis.api_name')
            ->get(['apis.id', 'apis.api_name']);

        $services = Schema::hasTable('services')
            ? DB::table('services')
                ->where('services.status', 1)
                ->where(function ($q) {
                    $q->whereNull('services.deleted_at')->orWhere('services.deleted_at', '!=', 1);
                })
                ->orderBy('services.service_name')
                ->get(['services.id', 'services.service_name'])
            : collect();

        return view('admin.routings.api-switching', compact('operators', 'apis', 'services'));
    }

    public function apiSwitchingList(Request $request)
    {
        $columns = [
            'providers.id as operator_id',
            'providers.provider_name as operator_name',
            DB::raw('COALESCE(services.service_name, providers.operator_type) as operator_type'),
            'providers.service_id',
            'providers.provider_logo',
            'providers.api_id',
            'primary_api.api_name',
        ];

        $query = $this->operatorsQuery()
            ->leftJoin('services', 'services.id', '=', 'providers.service_id')
            ->leftJoin('apis as primary_api', 'primary_api.id', '=', 'providers.api_id');

        if ($request->filled('operator_name')) {
            $query->where('providers.provider_name', 'like', '%' . trim((string) $request->operator_name) . '%');
        }

        if ($request->filled('operator_type')) {
            $operatorType = trim((string) $request->operator_type);
            if (is_numeric($operatorType)) {
                $query->where('providers.service_id', (int) $operatorType);
            } else {
                $query->whereRaw('COALESCE(services.service_name, providers.operator_type) like ?', ['%' . $operatorType . '%']);
            }
        }

        foreach (['backup_api_id', 'backup_api2_id', 'backup_api3_id'] as $index => $column) {
            if (!Schema::hasColumn('providers', $column)) {
                continue;
            }

            $alias = 'backup_api_' . ($index + 1);
            $query->leftJoin("apis as {$alias}", "{$alias}.id", '=', "providers.{$column}");
            $columns[] = "providers.{$column}";
            $columns[] = "{$alias}.api_name as {$alias}_name";
        }

        $rows = $query->orderBy('providers.provider_name')->get($columns)
            ->map(function ($row) {
                $row->logo_url = $this->logoUrl($row->provider_logo ?? '');
                return $row;
            });

        return response()->json([
            'type' => 'success',
            'data' => $rows,
        ]);
    }

    public function apiSwitchingSave(Request $request)
    {
        $request->validate([
            'operator_id' => 'required|numeric|min:1',
            'api_id' => 'nullable|numeric|min:0',
            'backup_api_id' => 'nullable|numeric|min:0',
            'backup_api2_id' => 'nullable|numeric|min:0',
            'backup_api3_id' => 'nullable|numeric|min:0',
        ]);

        $operator = $this->operatorsQuery()->where('providers.id', (int) $request->operator_id)->first();
        if (!$operator) {
            return response()->json(['type' => 'error', 'message' => 'Operator not found.']);
        }

        $apiIds = [
            'api_id' => (int) ($request->api_id ?: 0),
            'backup_api_id' => (int) ($request->backup_api_id ?: 0),
            'backup_api2_id' => (int) ($request->backup_api2_id ?: 0),
            'backup_api3_id' => (int) ($request->backup_api3_id ?: 0),
        ];

        if (!$this->validateApiIds($apiIds)) {
            return response()->json(['type' => 'error', 'message' => 'Please select valid recharge APIs.']);
        }

        $payload = ['updated_at' => now()];
        foreach ($this->providerApiColumns() as $column) {
            $payload[$column] = $apiIds[$column] ?? 0;
        }

        DB::table('providers')->where('id', (int) $request->operator_id)->update($payload);
        $this->syncRoutingTable((int) $operator->service_id, (int) $request->operator_id, $apiIds);

        return response()->json(['type' => 'success', 'message' => 'API switching saved successfully.']);
    }

    public function apiSwitchingDelete(Request $request)
    {
        $request->validate([
            'operator_id' => 'required|numeric|min:1',
        ]);

        $operator = $this->operatorsQuery()->where('providers.id', (int) $request->operator_id)->first();
        if (!$operator) {
            return response()->json(['type' => 'error', 'message' => 'Operator not found.']);
        }

        $apiIds = [
            'api_id' => 0,
            'backup_api_id' => 0,
            'backup_api2_id' => 0,
            'backup_api3_id' => 0,
        ];

        $payload = ['updated_at' => now()];
        foreach ($this->providerApiColumns() as $column) {
            $payload[$column] = 0;
        }

        DB::table('providers')->where('id', (int) $request->operator_id)->update($payload);
        $this->syncRoutingTable((int) $operator->service_id, (int) $request->operator_id, $apiIds);

        return response()->json(['type' => 'success', 'message' => 'API switching cleared successfully.']);
    }

    public function downUsers(Request $request)
    {
        $request->validate([
            'operator_id' => 'required|numeric|min:1',
        ]);

        $operator = $this->operatorsQuery()->where('providers.id', (int) $request->operator_id)->first(['providers.id', 'providers.provider_name']);
        if (!$operator) {
            return response()->json(['type' => 'error', 'message' => 'Operator not found.']);
        }

        $rows = DB::table('provider_user_downs as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.user_id')
            ->where('d.provider_id', (int) $request->operator_id)
            ->where('d.status', 1)
            ->orderByDesc('d.id')
            ->get([
                'd.id',
                'd.user_id',
                'u.first_name',
                'u.middle_name',
                'u.last_name',
                'u.outlet_name',
                'u.mobile_number',
            ])
            ->map(function ($row) {
                $name = trim(($row->first_name ?? '') . ' ' . ($row->middle_name ?? '') . ' ' . ($row->last_name ?? ''));
                $row->user_name = $name !== '' ? $name : ($row->outlet_name ?: ('User #' . $row->user_id));
                return $row;
            });

        return response()->json([
            'type' => 'success',
            'operator_name' => $operator->provider_name,
            'data' => $rows,
        ]);
    }

    public function searchUsers(Request $request)
    {
        $term = trim((string) $request->q);
        $operatorId = (int) ($request->operator_id ?: 0);

        $query = DB::table('users')
            ->where('role_id', '!=', 1)
            ->limit(20);

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('outlet_name', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('middle_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('mobile_number', 'like', "%{$term}%")
                    ->orWhere('id', $term);
            });
        }

        if ($operatorId > 0) {
            $query->whereNotIn('id', DB::table('provider_user_downs')
                ->select('user_id')
                ->where('provider_id', $operatorId)
                ->where('status', 1));
        }

        $users = $query->get(['id', 'first_name', 'middle_name', 'last_name', 'outlet_name', 'mobile_number'])->map(function ($user) {
            $name = trim(($user->first_name ?? '') . ' ' . ($user->middle_name ?? '') . ' ' . ($user->last_name ?? ''));
            $label = $user->id . ' - ' . ($name !== '' ? $name : ($user->outlet_name ?: 'User'));
            if ($user->outlet_name) {
                $label .= ' | ' . $user->outlet_name;
            }
            if ($user->mobile_number) {
                $label .= ' | ' . $user->mobile_number;
            }

            return ['id' => $user->id, 'text' => $label];
        });

        return response()->json(['type' => 'success', 'data' => $users]);
    }

    public function addDownUser(Request $request)
    {
        $request->validate([
            'operator_id' => 'required|numeric|min:1',
            'user_id' => 'required|numeric|min:1',
        ]);

        $operator = $this->operatorsQuery()->where('providers.id', (int) $request->operator_id)->first(['providers.id']);
        if (!$operator) {
            return response()->json(['type' => 'error', 'message' => 'Operator not found.']);
        }

        $user = DB::table('users')->where('id', (int) $request->user_id)->first(['id']);
        if (!$user) {
            return response()->json(['type' => 'error', 'message' => 'User not found.']);
        }

        $keys = [
            'provider_id' => (int) $request->operator_id,
            'user_id' => (int) $request->user_id,
        ];
        $existing = DB::table('provider_user_downs')->where($keys)->first();

        if ($existing) {
            DB::table('provider_user_downs')->where('id', $existing->id)->update([
                'status' => 1,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('provider_user_downs')->insert($keys + [
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['type' => 'success', 'message' => 'User wise down added successfully.']);
    }

    public function removeDownUser(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|min:1',
        ]);

        DB::table('provider_user_downs')->where('id', (int) $request->id)->delete();

        return response()->json(['type' => 'success', 'message' => 'User wise down removed successfully.']);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'operator_id' => 'required|numeric|min:1',
            'status' => 'required|in:0,1',
        ]);

        $updated = $this->operatorsQuery()
            ->where('providers.id', (int) $request->operator_id)
            ->update([
                'status' => (int) $request->status,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return response()->json(['type' => 'error', 'message' => 'Operator not found.']);
        }

        return response()->json([
            'type' => 'success',
            'message' => ((int) $request->status === 1) ? 'Operator turned ON.' : 'Operator turned OFF.',
        ]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'operator_id' => 'required|numeric|min:1',
        ]);

        $operator = $this->operatorsQuery()
            ->where('providers.id', (int) $request->operator_id)
            ->first(['providers.id']);

        if (!$operator) {
            return response()->json(['type' => 'error', 'message' => 'Operator not found.']);
        }

        DB::table('providers')->where('id', (int) $request->operator_id)->update([
            'deleted_at' => 1,
            'updated_at' => now(),
        ]);

        return response()->json(['type' => 'success', 'message' => 'Operator deleted successfully.']);
    }
}
