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

        $report_s = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id',Session::get('user_id'))
        ->where('transaction_type', 'Recharge')
        ->where('status', 'Success')
        ->get();
    $report_p = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id',Session::get('user_id'))
        ->where('transaction_type', 'Recharge')
        ->where('status', 'Pending')
        ->get();
    $report_f = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id',Session::get('user_id'))
        ->where('transaction_type', 'Recharge')
        ->where('status', 'Failed')
        ->get();
    $report_r = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id',Session::get('user_id'))
        ->where('transaction_type', 'Refund')
        ->where('status', 'Success')
        ->get();
    $report_receive_money = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id',Session::get('user_id'))
        ->where('transaction_type', 'Receive Money')
        ->where('status', 'Success')
        ->get();
    $report_upi_add_money = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
                ->where('user_id',Session::get('user_id'))
                ->where('transaction_type', 'Upi Add Money')
                ->where('status', 'Success')
                ->get();        
    $tranaction_reports = DB::table('reports')->where('user_id',Session::get('user_id'))
        ->where('transaction_type','Recharge')
        ->orderBy('created_at', 'DESC')->take(5)
        ->get();  
    // $fund_request = FundRequest::where('user_id',Session::get('user_id'))
    //         ->orderBy('created_at', 'DESC')->take(5)
    //         ->get(); 
    $fund_reports = DB::table('reports')->where('user_id',Session::get('user_id'))
            ->whereIn('transaction_type',['Transfer Money','Receive Money','Self Money','Money Reverse','Reverse Money'])
            ->orderBy('created_at', 'DESC')->take(5)
            ->get();
    $user = DB::table('users')->where('id',Session('user_id'))->first(); 
 //   if($user->role_id==3||$user->role_id==6){

        $Report_Success_Commission = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id',Session::get('user_id'))
        ->where('transaction_type', 'Recharge')
        ->where('status', 'Success')
        ->get();
        $Report_Pending_Commission = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id',Session::get('user_id'))
        ->where('transaction_type', 'Recharge')
        ->where('status', 'Pending')
        ->get();
        $Report_Parent_Commission = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id',Session::get('user_id'))
        ->where('transaction_type', 'Commission')
        ->where('status', 'Success')
        ->get();
        $Report_Parent_Reverse_Commission = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id',Session::get('user_id'))
        ->where('transaction_type', 'Reverse Commission')
        ->where('status', 'Success')
        ->get();

        $Total_Complaints_Count = DB::table('complaints')
        //->whereBetween('created_at', [$from_date,$to_date])
        ->whereIn('status', ['Open','Under Review'])
        ->where('user_id',Session::get('user_id'))
        ->get();
        //echo "<pre>";print_r($Report_Success->sum('commission'));die;

   // }       
       // echo "<pre>";print_r($user);die; 
        $data_n['rc_success_amount'] = $report_s->sum('total_amount');
        $data_n['rc_success_hit'] = $report_s->count();
        $data_n['rc_pending_amount'] = $report_p->sum('total_amount');
        $data_n['rc_pending_hit'] = $report_p->count();
        $data_n['rc_failed_amount'] = $report_f->sum('total_amount');
        $data_n['rc_failed_hit'] = $report_f->count();
        $data_n['rc_refund_amount'] = $report_r->sum('total_amount');
        $data_n['rc_refund_hit'] = $report_r->count();
        $data_n['rc_receive_money'] = $report_receive_money->sum('total_amount');
        $data_n['rc_upi_add_money'] = $report_upi_add_money->sum('total_amount');
        $data_n['rc_commission'] = $Report_Success_Commission->sum('commission') + $Report_Pending_Commission->sum('commission') + $Report_Parent_Commission->sum('amount') - $Report_Parent_Reverse_Commission->sum('amount');
        
        $data_n['rc_complaint_hit'] = $Total_Complaints_Count->count();
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
            SUM(CASE WHEN r.status = 'Pending' THEN total_amount ELSE 0 END) PendingAmt,
            SUM(CASE WHEN r.status = 'Failed' THEN total_amount ELSE 0 END) FailedAmt,
            SUM(CASE WHEN r.status = 'Success' THEN total_amount ELSE 0 END) SuccessAmt,
            SUM(r.total_amount) 'TotalAmt',
            SUM(CASE WHEN r.status = 'Success' THEN commission ELSE 0 END) Comm
            FROM `reports` as r JOIN users as u ON u.id=r.user_id
            JOIN providers as p ON p.id=r.provider_id 
            JOIN services as s ON s.id=p.service_id 
            WHERE r.created_at between '$from_date' AND '$to_date' AND transaction_type IN ('Recharge', 'Bill Pay') AND r.user_id = '".$user->id."'
            group by p.provider_name,s.service_name";
        //echo "<pre>";print_r($provider_sale_query);//die;
        $provider_sale_reports = DB::select($provider_sale_query);
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
