<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlanInfoFetchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanCircleDthApiController extends Controller
{
    public function __construct()
    {
        PlanInfoFetchService::ensureTable();
    }

    public function index()
    {
        $settings = PlanInfoFetchService::settingsForDisplay();
        $apis = PlanInfoFetchService::apiOptions();
        $apiCatalog = $apis->keyBy('id')->map(function ($api) {
            return [
                'api_name' => $api->api_name,
                'api_username' => $api->api_username,
                'api_password' => $api->api_password,
                'api_key' => $api->api_key,
            ];
        });

        return view('admin.system.plan-circle-dth-api', compact('settings', 'apis', 'apiCatalog'));
    }

    public function list()
    {
        $settings = PlanInfoFetchService::allSettings()->map(function ($row) {
            return [
                'service_key' => $row->service_key,
                'service_label' => $row->service_label,
                'is_routing' => (bool) $row->is_routing,
                'sort_order' => (int) $row->sort_order,
                'primary_api_id' => $row->primary_api_id,
                'primary_username' => $row->primary_username ?? '',
                'primary_password' => $row->primary_password ?? '',
                'backup_api_id' => $row->backup_api_id,
                'backup_username' => $row->backup_username ?? '',
                'backup_password' => $row->backup_password ?? '',
            ];
        });

        return response()->json([
            'type' => 'success',
            'data' => $settings,
            'apis' => PlanInfoFetchService::apiOptions(),
        ]);
    }

    public function save(Request $request)
    {
        $rows = $request->input('rows', []);
        if (!is_array($rows) || empty($rows)) {
            return response()->json(['type' => 'error', 'message' => 'No settings received.']);
        }

        PlanInfoFetchService::saveSettings($rows);

        return response()->json([
            'type' => 'success',
            'message' => 'Plan/Circle/DTH Info Fetch API settings saved successfully.',
        ]);
    }

    public function reset()
    {
        DB::table('plan_info_fetch_settings')->truncate();
        PlanInfoFetchService::seedDefaults();

        return response()->json([
            'type' => 'success',
            'message' => 'Settings reset to defaults.',
        ]);
    }
}
