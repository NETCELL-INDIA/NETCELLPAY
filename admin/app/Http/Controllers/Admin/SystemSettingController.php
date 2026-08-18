<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemSettingController extends Controller
{
    public const PAGES = [
        'system' => 'System setting',
        'service' => 'Service setting',
        'account-activation' => 'Account activation charge',
        'add-money-charge' => 'Add money charge setting',
        'payment-gateway' => 'Payment gateway setting',
        'min-add-money' => 'Minimum add money & signup',
        'pusher' => 'Pusher setting',
    ];

    public function show(Request $post, $page = 'system')
    {
        $page = $page ?: 'system';
        if (!isset(self::PAGES[$page])) {
            abort(404);
        }

        $settings = SystemSettingService::all();
        $services = collect();
        $company = null;
        try {
            $services = DB::table('services')->orderBy('id')->get(['id', 'service_name', 'status']);
        } catch (\Throwable $e) {
        }
        try {
            $company = DB::table('companies')->where('id', 1)->first();
        } catch (\Throwable $e) {
        }

        try {
            return view('admin.system-settings.index', [
                'page' => $page,
                'pageTitle' => self::PAGES[$page],
                'pages' => self::PAGES,
                'settings' => $settings,
                'services' => $services,
                'company' => $company,
            ]);
        } catch (\Throwable $e) {
            \Log::error('System setting page failed: '.$e->getMessage());
            return response(
                '<div style="padding:24px;font-family:sans-serif"><h3>System Setting could not load</h3><p>'.e($e->getMessage()).'</p></div>',
                200
            );
        }
    }

    public function save(Request $post)
    {
        $page = (string) $post->page;
        if (!isset(self::PAGES[$page])) {
            return response()->json(['type' => 'error', 'message' => 'Invalid settings page']);
        }

        if ($page === 'system') {
            SystemSettingService::putMany([
                'fund_interval_minute' => (int) $post->fund_interval_minute,
                'min_fund_transfer' => (float) $post->min_fund_transfer,
                'balance_alert_below' => (float) $post->balance_alert_below,
                'wrong_login_attempt' => (int) $post->wrong_login_attempt,
                'interval_recharge_minute' => (int) $post->interval_recharge_minute,
                'max_fund_transfer' => (float) $post->max_fund_transfer,
                'referral_amount' => (float) $post->referral_amount,
                'max_payout_account' => (int) $post->max_payout_account,
                'stop_all_transactions' => $post->boolean('stop_all_transactions') ? '1' : '0',
                'app_without_login' => $post->boolean('app_without_login') ? '1' : '0',
            ]);
        } elseif ($page === 'service') {
            $ids = $post->input('service_status', []);
            if (!is_array($ids)) {
                $ids = [];
            }
            DB::table('services')->update(['status' => 0]);
            if (!empty($ids)) {
                DB::table('services')->whereIn('id', array_map('intval', $ids))->update(['status' => 1]);
            }
        } elseif ($page === 'account-activation') {
            SystemSettingService::putMany([
                'activation_charge' => (float) $post->activation_charge,
                'activation_charge_status' => $post->boolean('activation_charge_status') ? '1' : '0',
            ]);
        } elseif ($page === 'add-money-charge') {
            SystemSettingService::putMany([
                'add_money_charge_type' => in_array($post->add_money_charge_type, ['fixed', 'percent'], true) ? $post->add_money_charge_type : 'fixed',
                'add_money_charge_value' => (float) $post->add_money_charge_value,
                'add_money_charge_status' => $post->boolean('add_money_charge_status') ? '1' : '0',
            ]);
        } elseif ($page === 'payment-gateway') {
            DB::table('companies')->where('id', 1)->update([
                'payment_gateway' => (int) $post->payment_gateway,
                'payment_gateway_min' => $post->payment_gateway_min,
                'payment_gateway_max' => $post->payment_gateway_max,
                'payment_gateway_key' => (string) $post->payment_gateway_key,
                'payment_gateway2' => (int) $post->payment_gateway2,
                'payment_gateway2_min' => $post->payment_gateway2_min,
                'payment_gateway2_max' => $post->payment_gateway2_max,
                'payment_gateway2_key' => (string) $post->payment_gateway2_key,
                'updated_at' => now(),
            ]);
        } elseif ($page === 'min-add-money') {
            SystemSettingService::putMany([
                'min_add_money' => (float) $post->min_add_money,
                'min_signup_amount' => (float) $post->min_signup_amount,
            ]);
        } elseif ($page === 'pusher') {
            SystemSettingService::putMany([
                'pusher_app_id' => trim((string) $post->pusher_app_id),
                'pusher_key' => trim((string) $post->pusher_key),
                'pusher_secret' => trim((string) $post->pusher_secret),
                'pusher_cluster' => trim((string) $post->pusher_cluster),
                'fcm_server_key' => trim((string) $post->fcm_server_key),
            ]);
        }

        return response()->json(['type' => 'success', 'message' => 'Setting saved successfully']);
    }
}
