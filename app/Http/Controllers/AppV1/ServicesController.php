<?php

namespace App\Http\Controllers\AppV1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\BillPaymentsController;
use App\Http\Controllers\Users\RechargeController;
use App\Services\BbpsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicesController extends Controller
{
    public function servicesList(Request $post)
    {
        $services = BbpsService::servicesWithProviders();

        return response()->json([
            'type' => 'success',
            'message' => 'Services loaded.',
            'recharge_services' => $services['recharge'],
            'bbps_services' => $services['bbps'],
            'mobile_provider' => \helpers::providersForApp(1),
            'dth_provider' => \helpers::providersForApp(2),
            'postpaid_provider' => \helpers::providersForApp([4, 15]),
        ]);
    }

    public function serviceProviders(Request $post)
    {
        $validator = \Validator::make($post->all(), ['service_id' => 'required|numeric']);
        if ($validator->fails()) {
            return response()->json(['type' => 'error', 'message' => $validator->errors()->first()]);
        }

        $providers = DB::table('providers')
            ->where('service_id', (int) $post->service_id)
            ->where('status', 1)
            ->where('deleted_at', 0)
            ->orderBy('provider_name')
            ->get(['id', 'provider_name', 'provider_logo', 'service_id']);

        return response()->json([
            'type' => 'success',
            'message' => 'Providers loaded.',
            'data' => \helpers::decorateProviderLogos($providers),
        ]);
    }

    public function billParams(Request $post)
    {
        return app(BillPaymentsController::class)->fetchProviderParams($post);
    }

    public function billFetch(Request $post)
    {
        return app(BillPaymentsController::class)->fetchBill($post);
    }

    public function billPay(Request $post)
    {
        $post->merge([
            'transaction_type' => 'Bill Pay',
        ]);

        return app(RechargeController::class)->rechargeCall($post);
    }
}
