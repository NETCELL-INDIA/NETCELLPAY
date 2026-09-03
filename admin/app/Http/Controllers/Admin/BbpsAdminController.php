<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAudit;
use App\Services\SystemSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BbpsAdminController extends Controller
{
    public function index()
    {
        $this->ensureParamsTable();
        $apis = DB::table('apis')
            ->where(function ($w) {
                $w->whereNull('deleted_at')->orWhere('deleted_at', '!=', 1);
            })
            ->orderBy('api_name')
            ->get(['id', 'api_name']);
        $fetchApi = SystemSettingService::get('bbps_fetch_api_id', '7');
        $paramsApi = SystemSettingService::get('bbps_params_api_id', '22');
        $services = DB::table('services')
            ->where(function ($w) {
                $w->whereNull('deleted_at')->orWhere('deleted_at', '!=', 1);
            })
            ->whereNotIn('id', [1, 2])
            ->orderBy('service_name')
            ->get(['id', 'service_name']);

        return view('admin.apis.bbps', compact('apis', 'fetchApi', 'paramsApi', 'services'));
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'bbps_fetch_api_id' => 'required|numeric',
            'bbps_params_api_id' => 'required|numeric',
        ]);
        SystemSettingService::putMany([
            'bbps_fetch_api_id' => (string) $request->bbps_fetch_api_id,
            'bbps_params_api_id' => (string) $request->bbps_params_api_id,
        ]);
        AdminAudit::log('bbps', 'bbps_api_settings', [
            'ref_type' => 'system',
            'ref_id' => 'bbps',
            'new' => [
                'fetch' => $request->bbps_fetch_api_id,
                'params' => $request->bbps_params_api_id,
            ],
        ]);

        return response()->json(['type' => 'success', 'message' => 'BBPS API settings saved']);
    }

    public function list(Request $request)
    {
        $this->ensureParamsTable();
        $serviceId = (int) $request->service_id;
        $term = trim((string) $request->q);
        $limit = in_array((int) $request->show, [10, 25, 50], true) ? (int) $request->show : 10;
        $page = max(1, (int) ($request->page ?: 1));
        $offset = ($page - 1) * $limit;
        $paramsApi = (int) SystemSettingService::get('bbps_params_api_id', '22');

        $q = DB::table('providers as p')
            ->leftJoin('services as s', 's.id', '=', 'p.service_id')
            ->where(function ($w) {
                $w->whereNull('p.deleted_at')->orWhere('p.deleted_at', '!=', 1);
            })
            ->whereNotIn('p.service_id', [1, 2]);
        if ($serviceId > 0) {
            $q->where('p.service_id', $serviceId);
        }
        if ($term !== '') {
            $q->where(function ($w) use ($term) {
                $w->where('p.provider_name', 'like', "%{$term}%")
                    ->orWhere('p.id', $term);
            });
        }
        $total = (clone $q)->count();
        $rows = (clone $q)->orderBy('p.provider_name')->offset($offset)->limit($limit)
            ->get(['p.id', 'p.provider_name', 'p.service_id', 'p.api_id', 'p.status', 's.service_name']);

        $html = '';
        if ($rows->count()) {
            foreach ($rows as $row) {
                $codeApi = (int) ($row->api_id ?: $paramsApi);
                $code = \Helper::ApiProviderCode($codeApi, $row->id) ?: '';
                $has = false;
                if ($code !== '' && Schema::hasTable('bbps_operator_params')) {
                    $has = DB::table('bbps_operator_params')->where('biller_id', $code)->exists();
                }
                $html .= '<tr>
                    <td>'.e($row->id).'</td>
                    <td>'.e($row->provider_name).'</td>
                    <td>'.e($row->service_name).'</td>
                    <td>'.e($code ?: '-').'</td>
                    <td>'.($has ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-warning text-dark">No</span>').'</td>
                    <td>'.((int) $row->status === 1 ? 'ON' : 'OFF').'</td>
                    <td><button type="button" class="btn btn-sm btn-outline-primary btn-bbps-edit" data-id="'.$row->id.'">Biller</button></td>
                </tr>';
            }
        } else {
            $html = '<tr><td colspan="7" class="text-center text-muted py-4">No billers</td></tr>';
        }

        return response()->json([
            'type' => 'success',
            'rows' => $html,
            'pagination' => [
                'page' => $page,
                'show' => $limit,
                'total' => $total,
                'from' => $total ? $offset + 1 : 0,
                'to' => min($offset + $limit, $total),
                'last_page' => max(1, (int) ceil($total / max($limit, 1))),
            ],
        ]);
    }

    public function getBiller(Request $request)
    {
        $this->ensureParamsTable();
        $id = (int) $request->id;
        $p = DB::table('providers')->where('id', $id)->first();
        if (! $p) {
            return response()->json(['type' => 'error', 'message' => 'Provider not found']);
        }
        $paramsApi = (int) SystemSettingService::get('bbps_params_api_id', '22');
        $codeApi = (int) ($p->api_id ?: $paramsApi);
        $code = \Helper::ApiProviderCode($codeApi, $p->id) ?: '';
        $biller = null;
        if ($code !== '') {
            $biller = DB::table('bbps_operator_params')->where('biller_id', $code)->first();
        }

        return response()->json([
            'type' => 'success',
            'data' => [
                'provider_id' => $p->id,
                'provider_name' => $p->provider_name,
                'biller_id' => $biller->biller_id ?? $code,
                'category_id' => $biller->category_id ?? $p->service_id,
                'biller_data' => $biller->biller_data ?? '',
            ],
        ]);
    }

    public function saveBiller(Request $request)
    {
        $this->ensureParamsTable();
        $request->validate([
            'provider_id' => 'required|numeric',
            'biller_id' => 'required|string|max:120',
            'category_id' => 'nullable|max:40',
            'biller_data' => 'nullable|string',
        ]);
        $json = trim((string) $request->biller_data);
        if ($json !== '') {
            json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['type' => 'error', 'message' => 'Biller data must be valid JSON']);
            }
        }
        $existing = DB::table('bbps_operator_params')->where('biller_id', $request->biller_id)->first();
        $payload = [
            'biller_id' => $request->biller_id,
            'category_id' => $request->category_id,
            'biller_data' => $json,
        ];
        if ($existing) {
            DB::table('bbps_operator_params')->where('id', $existing->id)->update($payload);
        } else {
            DB::table('bbps_operator_params')->insert($payload);
        }
        AdminAudit::log('bbps', 'bbps_biller_save', [
            'ref_type' => 'provider',
            'ref_id' => $request->provider_id,
            'new' => $request->biller_id,
        ]);

        return response()->json(['type' => 'success', 'message' => 'Biller parameters saved']);
    }

    private function ensureParamsTable(): void
    {
        try {
            if (! Schema::hasTable('bbps_operator_params')) {
                Schema::create('bbps_operator_params', function ($table) {
                    $table->id();
                    $table->string('biller_id', 120)->index();
                    $table->string('category_id', 40)->nullable();
                    $table->longText('biller_data')->nullable();
                });
            }
        } catch (\Throwable $e) {
        }
    }
}
