<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Session;
use Symfony\Component\HttpFoundation\StreamedResponse;
class FundReportsController extends Controller
{
    public function index(Request $post)
    {
        return view('admin.fund.fund-report');
    }

    public function fetchAll(Request $post)
    {
        $rules = array(
            'page' => 'required|numeric',
            'limit' => 'required|numeric',
            'tr_type' => 'required|in:All,Transfer Money,Receive Money,Upi Add Money,Self Money,Reverse Money,Money Reverse',
            'fund_type' => 'required|in:All,Credit,Debit',
            'user_id' => 'numeric',
        );

        $validator = \Validator::make($post->all(), array_reverse($rules));
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                $error = $value[0];
            }
            return '<h4 class="text-center text-secondary my-3">'.$error.'</h4>';
        }

        if($post->page){
            $page = $post->page; 
        }else{
            $page = 1; 
        }

        if($post->limit){
            if($post->limit <= 50){
                $limit = $post->limit; 
            }else{
                $limit = 10;  
            }
        }else{
            $limit = 10; 
        }

        if($post->user_id ==0){
            $post->user_id = '';
        }

        if($post->tr_type =="All"){
            $post->tr_type = '';
        }

        if($post->fund_type =="All"){
            $post->fund_type = '';
        }

        if($post->from_date){
            $from_date = $post->from_date." 00:00:00";
            $to_date = $post->to_date." 23:59:59";
            if($post->tbl_type == 0){
                $table = "reports";
            }else{
                $table = "backup_reports";
            }
            // if($post->user_id == ""){
            //     $user_id = 1;
            // }else{
            //     $user_id = $post->user_id;
            // }
        }else{
            $from_date = Carbon::today()->format('Y-m-d')." 00:00:00";
            $to_date = Carbon::today()->format('Y-m-d')." 23:59:59";
            $table = "reports";
            //$user_id = Session::get('user_id');
        }
        
        $start= ($page-1) * $limit;
        $total_row = DB::table($table)
            ->whereBetween('created_at', [$from_date,$to_date])
            ->where('user_id', 'like', '%' . $post->user_id . '%')
            ->where('order_id', 'like', '%' . $post->order_id . '%')
            ->where('fund_type', 'like', '%' . $post->fund_type . '%')
            ->where('transaction_type', 'like', '%' . $post->tr_type . '%')
            //->where('transaction_type', $post->tr_type)
            ->whereNotIn('transaction_type',['Commission','Recharge','Reverse Commission','Refund','Money Transfer'])
            ->orderBy('id', 'DESC')
            ->get();
        $total_row_count = $total_row->count();
        $total_pages = ceil($total_row_count / $limit);
        $page_link = '';
        for ($i1=1; $i1<=$total_pages; $i1++) {
            if($page == $i1){
                $act = "active";
                $d = "";
            }else{
                $act = "";
                $d = 'onclick="tableSearch('.$i1.')"';
            }
            $page_link .= '<li class="page-item "><a href="javascript:void(0)" class="page-link '.$act.'" '.$d.'>'.$i1.'</a></li>';
        };
        //$transaction_type = "'Transfer Money'";
        
        $list = DB::table($table)
            ->whereBetween('created_at', [$from_date,$to_date])
            ->where('user_id', 'like', '%' . $post->user_id . '%')
            ->where('order_id', 'like', '%' . $post->order_id . '%')
            ->where('transaction_type', 'like', '%' . $post->tr_type . '%')
            //->whereIn('transaction_type',['Transfer Money','Receive Money','Upi Add Money','Self Money','Money Reverse','Reverse Money'])
            ->whereNotIn('transaction_type',['Commission','Recharge','Reverse Commission','Refund','Money Transfer'])
            ->where('fund_type', 'like', '%' . $post->fund_type . '%')
            ->orderBy('id', 'DESC')
            ->offset($start)
            ->limit($limit)
            ->get();
        $list_count = $list->count();

        //////
        $all_list = DB::table($table)
        ->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id', 'like', '%' . $post->user_id . '%')
        ->where('transaction_type', 'like', '%' . $post->tr_type . '%')
        ->whereNotIn('transaction_type',['Commission','Recharge','Reverse Commission','Refund','Money Transfer'])
        ->where('fund_type', 'like', '%' . $post->fund_type . '%')
        ->orderBy('id', 'DESC')
        ->get();
        //////
        $tm_list = DB::table($table)
        ->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id', 'like', '%' . $post->user_id . '%')
        ->where('transaction_type', 'like', '%' . $post->tr_type . '%')
        ->whereIn('transaction_type',['Transfer Money'])
        ->where('fund_type', 'like', '%' . $post->fund_type . '%')
        ->orderBy('id', 'DESC')
        ->get();
        /////////
        $rm_list = DB::table($table)
        ->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id', 'like', '%' . $post->user_id . '%')
        ->where('transaction_type', 'like', '%' . $post->tr_type . '%')
        ->whereIn('transaction_type',['Receive Money'])
        ->where('fund_type', 'like', '%' . $post->fund_type . '%')
        ->orderBy('id', 'DESC')
        ->get();
        ///////////////
        $rem_list = DB::table($table)
        ->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id', 'like', '%' . $post->user_id . '%')
        ->where('transaction_type', 'like', '%' . $post->tr_type . '%')
        ->whereIn('transaction_type',['Reverse Money'])
        ->where('fund_type', 'like', '%' . $post->fund_type . '%')
        ->orderBy('id', 'DESC')
        ->get();
         ///////////////
         $rem_list = DB::table($table)
         ->whereBetween('created_at', [$from_date,$to_date])
         ->where('user_id', 'like', '%' . $post->user_id . '%')
         ->where('transaction_type', 'like', '%' . $post->tr_type . '%')
         ->whereIn('transaction_type',['Reverse Money'])
         ->where('fund_type', 'like', '%' . $post->fund_type . '%')
         ->orderBy('id', 'DESC')
         ->get();
        //////////////
         $mr_list = DB::table($table)
         ->whereBetween('created_at', [$from_date,$to_date])
         ->where('user_id', 'like', '%' . $post->user_id . '%')
         ->where('transaction_type', 'like', '%' . $post->tr_type . '%')
         ->whereIn('transaction_type',['Money Reverse'])
         ->where('fund_type', 'like', '%' . $post->fund_type . '%')
         ->orderBy('id', 'DESC')
         ->get();
         $uam_list = DB::table($table)
         ->whereBetween('created_at', [$from_date,$to_date])
         ->where('user_id', 'like', '%' . $post->user_id . '%')
         ->where('transaction_type', 'like', '%' . $post->tr_type . '%')
         ->whereIn('transaction_type',['Upi Add Money'])
         ->where('fund_type', 'like', '%' . $post->fund_type . '%')
         ->orderBy('id', 'DESC')
         ->get();

        /////
        $output = '';
		if ($list->count() > 0) {
            $output .= '<div class="row p-0 m-0">
                <div class="col-2 border border-success text-success" style="border-radius: 25px;padding: 10px;padding-right: 5px;padding-left: 5px;"><b>Total : </b> <span class="lblSuccessAmount">' . $all_list->sum('amount') .'</span> (<span class="lblSuccessTxns">'.$all_list->count().'</span>)</div>
                <div class="col-2 border border-primary text-primary" style="border-radius: 25px;padding: 10px;padding-right: 5px;padding-left: 5px;"><b>Transfer Money : </b> <span class="lblPendingAmount">' . $tm_list->sum('amount') .'</span> (<span class="lblPendingTxns">'.$tm_list->count().'</span>)</div>
                <div class="col-2 border border-primary text-primary" style="border-radius: 25px;padding: 10px;padding-right: 5px;padding-left: 5px;"><b>Receive Money: </b> <span class="lblFailureAmount">' . $rm_list->sum('amount') .'</span> (<span class="lblFailureTxns">'.$rm_list->count().'</span>)</div>
                <div class="col-2 border border-primary text-primary" style="border-radius: 25px;padding: 10px;padding-right: 5px;padding-left: 5px;"> <b>Reverse Money : </b> <span class="lblRefundedAmount">' . $rem_list->sum('amount') .'</span> (<span class="lblRefundedTxns">'.$rem_list->count().'</span>)</div>
                <div class="col-2 border border-primary text-primary" style="border-radius: 25px;padding: 10px;padding-right: 5px;padding-left: 5px;"> <b>Money Reverse: </b> <span class="lblRefundedAmount">' . $mr_list->sum('amount') .'</span> (<span class="lblRefundedTxns">'.$mr_list->count().'</span>)</div>
                <div class="col-2 border border-primary text-primary" style="border-radius: 25px;padding: 10px;padding-right: 5px;padding-left: 5px;"> <b>Upi Add Money : </b> <span class="lblRefundedAmount">' . $uam_list->sum('amount') .'</span> (<span class="lblRefundedTxns">'.$uam_list->count().'</span>)</div>
            </div></br>';
			$output .= '<div class="table-responsive">';
            $output .= '<div class="row">
                    <div class="col-sm-1">
                        <select class="form-select mb-3 page_limit" aria-label="Page Limit" id="page_limit">
                            <option ' . ($limit == "5" ? "selected" : "") . ' value="5">5</option>
                            <option ' . ($limit == "10" ? "selected" : "") . ' value="10" >10</option>
                            <option ' . ($limit == "15" ? "selected" : "") . ' value="15">15</option>
                            <option ' . ($limit == "25" ? "selected" : "") . ' value="25">25</option>
                            <option ' . ($limit == "50" ? "selected" : "") . ' value="50">50</option>
                        </select>
                    </div>
                    <div class="col-sm-9">
                        </br>
                    </div>
                    
                    <div class="col-sm-2">
                        <input type="text" class="form-control" id="searchValueTable" placeholder="Enter Search Value">
                    </div>
                </div><br>';
            $output .= '<table class="table table-bordered table-nowrap" id="pagination_table"><thead>
              <tr>
                <th>ID</th>
                <th>User Details</th>
                <th>Date & Time</th>
                <th>Order Id</th>
                <th>Tr Type</th>
                <th>Credit By</th>
                <th>Debit By</th>
                <th>Remark</th>
                <th>Amount</th>
                <th>Credit/Debit</th>
                <th>Opening</th>
                <th>Closing</th>
              </tr>
            </thead>
            <tbody>';
            $i=$start + 1;
			foreach ($list as $list) {

                $credit = DB::table('users')->where('id', $list->credit_user_id)->first();
                if($credit){
                    $c_first_name = $credit->first_name;
                    $c_middle_name = $credit->middle_name;
                    $c_last_name = $credit->last_name;
                    $c_outlet_name = $credit->outlet_name;
                    $c_mobile_number = $credit->mobile_number;
                }else{
                    $c_first_name = "-";
                    $c_middle_name = "";
                    $c_last_name = "";
                    $c_outlet_name = "-";
                    $c_mobile_number = "-";
                }

                $debit = DB::table('users')->where('id', $list->debit_user_id)->first();
                if($debit){
                    $d_first_name = $debit->first_name;
                    $d_middle_name = $debit->middle_name;
                    $d_last_name = $debit->last_name;
                    $d_outlet_name = $debit->outlet_name;
                    $d_mobile_number = $debit->mobile_number;
                }else{
                    $d_first_name = "-";
                    $d_middle_name = "";
                    $d_last_name = "";
                    $d_outlet_name = "-";
                    $d_mobile_number = "-";
                }

                $user_dt = DB::table('users')->where('id', $list->user_id)->first();
                if($user_dt){
                    $user_dt_first_name = $user_dt->first_name;
                    $user_dt_middle_name = $user_dt->middle_name;
                    $user_dt_last_name = $user_dt->last_name;
                    $user_dt_outlet_name = $user_dt->outlet_name;
                    $user_dt_mobile_number = $user_dt->mobile_number;
                }else{
                    $user_dt_first_name = "-";
                    $user_dt_middle_name = "";
                    $user_dt_last_name = "";
                    $user_dt_outlet_name = "-";
                    $user_dt_mobile_number = "-";
                }

                if($list->fund_type == "Credit"){
                    $bg = "success";
                    $inr = "green";
                }elseif ($list->fund_type == "Debit") {
                    $bg = "danger";
                    $inr = "red";
                }else{
                    $bg = "warning";
                    $inr = "black";
                }
				$output .= '<tr>
                <td>' . $i . '</td>
                <td>
                    Name : '.$user_dt_first_name.' '.$user_dt_middle_name.' '.$user_dt_last_name.' </br>
                    Outlet Name : ' . $user_dt_outlet_name . ' </br>
                    Mobile No. : ' . $user_dt_mobile_number . ' </br>
                </td>
                <td>' . $list->transaction_date . '</td>
                <td>' . $list->order_id . '</td>
                <td>
                    <span class="badge badge-gradient-info">' . $list->transaction_type . '</span>
                </td>

                <td>
                    Name : '.$c_first_name.' '.$c_middle_name.' '.$c_last_name.' </br>
                    Outlet Name : ' . $c_outlet_name . ' </br>
                    Mobile No. : ' . $c_mobile_number . ' </br>
                </td>
                <td>
                    Name : '.$d_first_name.' '.$d_middle_name.' '.$d_last_name.' </br>
                    Outlet Name : ' . $d_outlet_name . ' </br>
                    Mobile No. : ' . $d_mobile_number . ' </br>
                </td>
                <td>' . $list->remark . '</td>
                <td style="color: '.$inr.';font-size: 18px;"> ₹ ' . $list->total_amount . '</td> 
                <td>
                    <span class="badge rounded-pill text-bg-' . $bg . '">' . $list->fund_type . '</span>
                </td>
                <td> ₹ ' . $list->opening_balance . '</td>
                <td> ₹ ' . $list->closing_balance . '</td>
              </tr>';
              $i++;
			}
			$output .= '</tbody></table>';
			$output .= '<div class="row">
                <div class="col-sm-2">
                        <span>Showing '.($start + 1).' to '.($start + $list_count).' of '.$list_count.' entries ('.$total_row_count.' entries)</span>
                </div>
                <div class="col-sm-6">
                <br>
                </div>
                <div class="col-sm-4">
                        <nav aria-label="Page navigation example">
                        <ul class="pagination">
                            <li class="page-item '.($page  == 1 || $page  == 0 ? "disabled" : "").'">
                                <a class="page-link" href="javascript:void(0)" onclick="tableSearch('.($page - 1).')">← &nbsp; Prev</a>
                            </li>
                                '.$page_link.'
                            <li class="page-item '.($page + 1 == $i1 ? "disabled" : "").'">
                                <a class="page-link" href="javascript:void(0)" onclick="tableSearch('.($page + 1 == $i1 ? $page : $page + 1).')">Next &nbsp; →</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div><br>';

            $output .= '</div>';
            echo $output;
		} else {
			echo '<h4 class="text-center text-secondary my-3">No record found</h4>';
		}
        
    }



    public function fetchAllExport(Request $post)

    {

        // Define the name of the output file

        $fileName = rand(100000,9999999).'.csv';


        if($post->user_id ==0){

            $post->user_id = '';

        }



        if($post->tr_type =="All"){

            $post->tr_type = '';

        }



        if($post->fund_type =="All"){

            $post->fund_type = '';

        }



        if($post->from_date){

            $from_date = $post->from_date." 00:00:00";

            $to_date = $post->to_date." 23:59:59";

            if($post->tbl_type == 0){

                $table = "reports";

            }else{

                $table = "backup_reports";

            }
        }else{

            $from_date = Carbon::today()->format('Y-m-d')." 00:00:00";

            $to_date = Carbon::today()->format('Y-m-d')." 23:59:59";

            $table = "reports";

            //$user_id = Session::get('user_id');

        }


        $response = new StreamedResponse(function() use ($fileName,$post,$from_date,$to_date,$table) {
            $handle = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($handle, [
                'Full Name',
                'Outlet Name',
                'Outlet No',
                'User Id',
                'Order Id',
                'Date/Time',
                'Tr Type',
                'Credit By',
                'Debit By',
                'Remark',
                'Amount',
                'Total Amount',
                'Fund Type',
                'Opening',
                'Closing',
                'Path',
                'IP Address',
            ]);
            DB::table($table)

            ->whereBetween('created_at', [$from_date,$to_date])
            ->where('user_id', 'like', '%' . $post->user_id . '%')
            ->where('order_id', 'like', '%' . $post->order_id . '%')
            ->where('transaction_type', 'like', '%' . $post->tr_type . '%')
            ->whereNotIn('transaction_type',['Commission','Recharge','Reverse Commission','Refund','Money Transfer'])
            ->where('fund_type', 'like', '%' . $post->fund_type . '%')
            ->orderBy('id', 'ASC')
            ->chunk(200, function($rows) use ($handle) {

                foreach ($rows as $list) {
                    $user_dt = DB::table('users')->where('id', $list->user_id)->first();

                    $credit = DB::table('users')->where('id', $list->credit_user_id)->first();

                    if($credit->id != 0){

                        $c_first_name = $credit->first_name;

                        $c_middle_name = $credit->middle_name;

                        $c_last_name = $credit->last_name;

                        $c_outlet_name = $credit->outlet_name;

                        $c_mobile_number = $credit->mobile_number;

                    }else{

                        $c_first_name = "-";

                        $c_middle_name = "";

                        $c_last_name = "";

                        $c_outlet_name = "-";

                        $c_mobile_number = "-";

                    }



                    $debit = DB::table('users')->where('id', $list->debit_user_id)->first();

                    if($debit->id !=0){

                        $d_first_name = $debit->first_name;

                        $d_middle_name = $debit->middle_name;

                        $d_last_name = $debit->last_name;

                        $d_outlet_name = $debit->outlet_name;

                        $d_mobile_number = $debit->mobile_number;

                    }else{

                        $d_first_name = "-";

                        $d_middle_name = "";

                        $d_last_name = "";

                        $d_outlet_name = "-";

                        $d_mobile_number = "-";

                    }
                    fputcsv($handle, [

                        $user_dt->first_name." ".$user_dt->middle_name." ".$user_dt->last_name,
                        $user_dt->outlet_name,
                        $user_dt->mobile_number,
                        $user_dt->id,
                        $list->order_id,
                        $list->transaction_date,
                        $list->transaction_type,
                        $c_first_name.' '.$c_middle_name.' '.$c_last_name.' '. $c_outlet_name . ' ' .$c_mobile_number,
                        $d_first_name.' '.$d_middle_name.' '.$d_last_name.' '. $d_outlet_name . ' ' .$d_mobile_number,
                        $list->remark,
                        round($list->amount, 2),
                        round($list->total_amount, 2),
                        $list->fund_type,
                        $list->opening_balance,
                        $list->closing_balance,
                        $list->path,
                        $list->ip_address,
                       

                    ]);

                }

            });
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }

    public function searchUuser(Request $post)
    {
        $keyword = trim((string) $post->keyword);
        $user = collect();
        if ($keyword !== '') {
            $user = DB::table('users')
                ->where('deleted_at', 0)
                ->where(function ($q) use ($keyword) {
                    $q->where('mobile_number', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('email_address', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('outlet_name', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('first_name', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('last_name', 'LIKE', '%' . $keyword . '%');
                })
                ->limit(25)
                ->get(['id', 'first_name', 'middle_name', 'last_name', 'outlet_name', 'mobile_number']);
        }
        return response()->json([
            'users' => $user
        ]);
    }

}
