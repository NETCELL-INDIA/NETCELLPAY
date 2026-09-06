<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Services\BbpsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Session;

class BillPaymentsController extends Controller
{
    public function index(Request $post)
    {
        $serviceId = (int) ($post->id ?: 3);
        $providers = \helpers::providersForApp($serviceId)->where('status', 1)->where('provider_down', 0)->values();

        $service = DB::table('services')->where('id', $serviceId)->where('deleted_at', 0)->first();
        if ($service && ((int) ($service->status ?? 1) !== 1 || (int) ($service->service_down ?? 0) === 1)) {
            $providers = collect();
        }
        $bbpsCategories = \helpers::serviceCatalogItems('bbps');

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

        $userId = Session::get('user_id');
        if ($userId && \helpers::isUserServiceLocked($userId, $provider->service_id)) {
            return response()->json(['type' => 'error', 'message' => 'This service is locked for your account. Contact admin.']);
        }

        $apiId = (int) ($provider->api_id ?: \App\Services\SystemSettingService::get('bbps_params_api_id', config('recharge_services.bbps_params_api_id', 22)));
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

        if (Session::get('user_id') && \helpers::isUserServiceLocked(Session::get('user_id'), (int) $post->service_id)) {
            return response()->json(['type' => 'error', 'message' => 'This service is locked for your account. Contact admin.']);
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
