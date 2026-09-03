<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Session;
class DashboardController extends Controller
{
    public function Dashboard(Request $post)
    {
        $slider_list = DB::table('sliders')
        ->where('status', 1)
        ->where('deleted_at', 0)
        ->where('user_id',1)
        ->get();
        $data['slider_list'] = $slider_list;
        $data['role_id'] = (int) Session::get('role_id');
        $data['is_retailer'] = $data['role_id'] === 6;
        $data['recharge_services'] = \helpers::serviceCatalogItems('recharge');
        $walletUser = DB::table('users')->where('id', Session::get('user_id'))->first();
        $data['balance_alert'] = (float) \App\Services\SystemSettingService::get('balance_alert_below', 500);
        $data['show_balance_alert'] = $walletUser && $data['balance_alert'] > 0
            && (float) $walletUser->wallet_balance < $data['balance_alert'];
        return view('users.dashboard', $data);
    }

    public function Services(Request $post)
    {
        return view('users.services');
    }


    public function dashboardReportsList(Request $post){

        if($post->from_date){
            $from_date = $post->from_date." 00:00:00";
            $to_date = $post->to_date." 23:59:59";
        }else{
            $from_date = Carbon::today()->format('Y-m-d')." 00:00:00";
            $to_date = Carbon::today()->format('Y-m-d')." 23:59:59";
        }

        $userId = (int) Session::get('user_id');
        $agg = DB::table('reports')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$from_date, $to_date])
            ->selectRaw("
                COALESCE(SUM(CASE WHEN transaction_type = 'Recharge' AND status = 'Success' THEN total_amount ELSE 0 END), 0) as rc_success_amount,
                COALESCE(SUM(CASE WHEN transaction_type = 'Recharge' AND status = 'Success' THEN 1 ELSE 0 END), 0) as rc_success_hit,
                COALESCE(SUM(CASE WHEN transaction_type = 'Recharge' AND status = 'Pending' THEN total_amount ELSE 0 END), 0) as rc_pending_amount,
                COALESCE(SUM(CASE WHEN transaction_type = 'Recharge' AND status = 'Pending' THEN 1 ELSE 0 END), 0) as rc_pending_hit,
                COALESCE(SUM(CASE WHEN transaction_type = 'Recharge' AND status = 'Failed' THEN total_amount ELSE 0 END), 0) as rc_failed_amount,
                COALESCE(SUM(CASE WHEN transaction_type = 'Recharge' AND status = 'Failed' THEN 1 ELSE 0 END), 0) as rc_failed_hit,
                COALESCE(SUM(CASE WHEN transaction_type = 'Refund' AND status = 'Success' THEN total_amount ELSE 0 END), 0) as rc_refund_amount,
                COALESCE(SUM(CASE WHEN transaction_type = 'Refund' AND status = 'Success' THEN 1 ELSE 0 END), 0) as rc_refund_hit,
                COALESCE(SUM(CASE WHEN transaction_type = 'Receive Money' AND status = 'Success' THEN total_amount ELSE 0 END), 0) as rc_receive_money,
                COALESCE(SUM(CASE WHEN transaction_type = 'Upi Add Money' AND status = 'Success' THEN total_amount ELSE 0 END), 0) as rc_upi_add_money,
                COALESCE(SUM(CASE WHEN transaction_type = 'Recharge' AND status IN ('Success','Pending') THEN commission ELSE 0 END), 0)
                    + COALESCE(SUM(CASE WHEN transaction_type = 'Commission' AND status = 'Success' THEN amount ELSE 0 END), 0)
                    - COALESCE(SUM(CASE WHEN transaction_type = 'Reverse Commission' AND status = 'Success' THEN amount ELSE 0 END), 0) as rc_commission
            ")
            ->first();

        $tranaction_reports = DB::table('reports')->where('user_id', $userId)
            ->where('transaction_type','Recharge')
            ->orderBy('created_at', 'DESC')->take(5)
            ->get();
        $fund_reports = DB::table('reports')->where('user_id', $userId)
            ->whereIn('transaction_type',['Transfer Money','Receive Money','Self Money','Money Reverse','Reverse Money'])
            ->orderBy('created_at', 'DESC')->take(5)
            ->get();
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return response()->json(['type' => 'error', 'message' => 'user not found']);
        }

        $data_n['rc_success_amount'] = (float) ($agg->rc_success_amount ?? 0);
        $data_n['rc_success_hit'] = (int) ($agg->rc_success_hit ?? 0);
        $data_n['rc_pending_amount'] = (float) ($agg->rc_pending_amount ?? 0);
        $data_n['rc_pending_hit'] = (int) ($agg->rc_pending_hit ?? 0);
        $data_n['rc_failed_amount'] = (float) ($agg->rc_failed_amount ?? 0);
        $data_n['rc_failed_hit'] = (int) ($agg->rc_failed_hit ?? 0);
        $data_n['rc_refund_amount'] = (float) ($agg->rc_refund_amount ?? 0);
        $data_n['rc_refund_hit'] = (int) ($agg->rc_refund_hit ?? 0);
        $data_n['rc_receive_money'] = (float) ($agg->rc_receive_money ?? 0) + (float) ($agg->rc_upi_add_money ?? 0);
        $data_n['rc_upi_add_money'] = (float) ($agg->rc_upi_add_money ?? 0);
        $data_n['rc_commission'] = (float) ($agg->rc_commission ?? 0);
        $data_n['rc_complaint_hit'] = (int) DB::table('complaints')
            ->whereIn('status', ['Open','Under Review'])
            ->where('user_id', $userId)
            ->count();
        //$data['tranaction_reports'] = $tranaction_reports;
        //$data['fund_request'] = $fund_request;
       // $data['fund_reports'] = $fund_reports;
       $data['rc_reports'] = $data_n;


       /////
       $provider_sale_query =  "SELECT 
            p.provider_name,
            s.service_name,
            COUNT(IF(r.status = 'Pending', 1, NULL)) 'PendingHit',
            COUNT(IF(r.status = 'Failed', 1, NULL)) 'FailedHit',
            COUNT(IF(r.status = 'Success', 1, NULL)) 'SuccessHit',
            COUNT(r.id) 'TotalHit' ,
            SUM(CASE WHEN r.status = 'Pending' THEN r.total_amount ELSE 0 END) PendingAmt,
            SUM(CASE WHEN r.status = 'Failed' THEN r.total_amount ELSE 0 END) FailedAmt,
            SUM(CASE WHEN r.status = 'Success' THEN r.total_amount ELSE 0 END) SuccessAmt,
            SUM(r.total_amount) 'TotalAmt',
            SUM(CASE WHEN r.status = 'Success' THEN r.commission ELSE 0 END) Comm
            FROM `reports` as r JOIN users as u ON u.id=r.user_id
            JOIN providers as p ON p.id=r.provider_id 
            JOIN services as s ON s.id=p.service_id 
            WHERE r.created_at between '$from_date' AND '$to_date' AND transaction_type IN ('Recharge', 'Bill Pay') AND r.user_id = '".$user->id."'
            group by p.provider_name,s.service_name";
        //echo "<pre>";print_r($provider_sale_query);//die;
        try {
            $provider_sale_reports = DB::select($provider_sale_query);
        } catch (\Throwable $e) {
            $provider_sale_reports = [];
        }
        //echo "<pre>";print_r($provider_sale_reports);die;
        $provider_list_output = '';
		if (count($provider_sale_reports) > 0) {
            $provider_list_output .= '<table id="scroll-vertical" class="table table-bordered dt-responsive nowrap align-middle mdl-data-table" style="width:100%;text-transform: uppercase;">
            <thead>
              <tr>
                <th>Provider Details</th>
                <th>Qty/Total Amount</th>
                <th>Qty/Success Amount</th>
                <th>Qty/Failed Amount</th>
                <th>Qty/Pending Amount</th>
                <th>Commission</th>
              </tr>
            </thead>
            <tbody>';
            $i=1;
            foreach ($provider_sale_reports as $list) {
                $provider_list_output .= '<tr>
                    <td>' . $list->provider_name . ' - ' . $list->service_name . '</td>
                    <td>' . $list->TotalHit . ' / ₹ ' . number_format((float) $list->TotalAmt, 2) . '</td>
                    <td>' . $list->SuccessHit . ' / ₹ ' . number_format((float) $list->SuccessAmt, 2). '</td>
                    <td>' . $list->FailedHit . ' / ₹ ' . number_format((float) $list->FailedAmt, 2) . '</td>
                    <td>' . $list->PendingHit . ' / ₹ ' . number_format((float) $list->PendingAmt, 2) . '</td>
                    <td>₹ ' . number_format((float) $list->Comm, 2) . '</td>
                </tr>';
                $i++;
            }
            $provider_list_output .= '</tbody></table>';
        }else{
            $provider_list_output = '<h4 class="text-center text-secondary my-3">No record found</h4>';
        }
        //echo "<pre>";print_r(count($provider_sale_reports));//die;
       $data['provider_list'] = $provider_list_output;
        $slider_list = DB::table('sliders')
        ->where('status', 1)
        ->where('deleted_at', 0)
        ->where('user_id',1)
        ->get();
        $data['slider_list'] = $slider_list;
       /////
        return $data;
    }

    

    
}
