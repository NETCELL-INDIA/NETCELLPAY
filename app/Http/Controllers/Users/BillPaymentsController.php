<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Services\BbpsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillPaymentsController extends Controller
{
    public function index(Request $post)
    {
        $serviceId = (int) ($post->id ?: 3);
        $providers = DB::table('providers')
            ->select('id', 'provider_name')
            ->where('service_id', $serviceId)
            ->where('deleted_at', '!=', 1)
            ->where('status', 1)
            ->orderBy('provider_name')
            ->get();

        $service = DB::table('services')->where('id', $serviceId)->where('deleted_at', 0)->first();
        $bbpsCategories = config('recharge_services.bbps', []);

        return view('users.services.bill-payments', [
            'providers' => $providers,
            'service' => $service->service_name ?? 'Bill Payments',
            'service_id' => $serviceId,
            'bbps_categories' => $bbpsCategories,
        ]);
    }

    public function fetchProviderParams(Request $post)
    {
        $validator = \Validator::make($post->all(), ['id' => 'required|numeric']);
        if ($validator->fails()) {
            return response()->json(['type' => 'error', 'message' => $validator->errors()->first()]);
        }

        $provider = DB::table('providers')->where('id', (int) $post->id)->first();
        if (!$provider) {
            return response()->json(['type' => 'error', 'message' => 'Provider not found.']);
        }

        $apiId = (int) ($provider->api_id ?: config('recharge_services.bbps_params_api_id', 22));
        $providerCode = \helpers::ApiProviderCode($apiId, (int) $post->id);

        if (!$providerCode) {
            return response()->json(['type' => 'error', 'message' => 'Biller code not configured. Contact admin.']);
        }

        $biller = DB::table('bbps_operator_params')
            ->select('id', 'biller_data')
            ->where('biller_id', $providerCode)
            ->first();

        if (!$biller) {
            return response()->json(['type' => 'error', 'message' => 'Biller parameters not found. Contact admin.']);
        }

        return response()->json([
            'type' => 'success',
            'message' => 'Parameters loaded.',
            'biller' => $biller,
            'provider' => $provider,
        ]);
    }

    public function fetchBill(Request $post)
    {
        $validator = \Validator::make($post->all(), [
            'provider_id' => 'required|numeric',
            'service_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['type' => 'error', 'message' => $validator->errors()->first()]);
        }

        $fields = $post->except(['_token', 'provider_id', 'service_id', 'login_key', 'user_id', 'api_key']);
        $result = BbpsService::fetchBill((int) $post->provider_id, $fields);

        if ($result['type'] === 'success') {
            $result['service_id'] = (int) $post->service_id;
            $result['provider_id'] = (int) $post->provider_id;
        }

        return response()->json($result);
    }

    public function payBill(Request $post)
    {
        $post->merge(['transaction_type' => 'Bill Pay']);

        return app(RechargeController::class)->rechargeCall($post);
    }
}
