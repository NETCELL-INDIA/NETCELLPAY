<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Session;
class FundController extends Controller
{
    public function index(Request $post)
    {
        return view('admin.fund.fund-request');
    }

    public function fetchAll(Request $post)
    {
        $rules = array(
            'page' => 'required|numeric',
            //'path' => 'required|numeric',
            'limit' => 'required|numeric',
            'status' => 'required|in:All,Pending,Transferred,Rejected',
            'user_id' => 'required|numeric',
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

        if($post->path == ""){
            $path = ''; 
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
        if($post->status =="All"){
            $post->status = '';
        }

        if($post->from_date){
            $from_date = $post->from_date." 00:00:00";
            $to_date = $post->to_date." 23:59:59";
            if($post->tbl_type == 0){
                $table = "fund_requests";
            }else{
                $table = "backup_fund_requests";
            }
        }else{
            $from_date = Carbon::today()->format('Y-m-d')." 00:00:00";
            $to_date = Carbon::today()->format('Y-m-d')." 23:59:59";
            $table = "fund_requests";
        }

        $start= ($page-1) * $limit;
        $total_row = DB::table($table)
        ->where('request_to', 1)
        ->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id', 'like', '%' . $post->user_id . '%')
        ->where('order_id', 'like', '%' . $post->order_id . '%')
        ->where('status', 'like', '%' . $post->status . '%')
        ->where('upi', 'like', '%' . $post->path . '%')
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

        //echo "<pre>";print_r($table);die;
        $list = DB::table($table)
        ->where('request_to', 1)
        ->whereBetween('created_at', [$from_date,$to_date])
        ->where('user_id', 'like', '%' . $post->user_id . '%')
        ->where('order_id', 'like', '%' . $post->order_id . '%')
        ->where('status', 'like', '%' . $post->status . '%')
        ->where('upi', 'like', '%' . $post->path . '%')
        //->where('user_id', 'like', $post->user_id)
        //->where('order_id', 'like', $post->order_id)
        ->orderBy('id', 'DESC')
        ->offset($start)
        ->limit($limit)
        ->get();
        $list_count = $list->count();

        //////
        $upi_req_s = DB::table('fund_requests')->whereBetween('created_at', [$from_date,$to_date])
        ->where('status', 'Transferred')
        ->where('upi', 'like', '%' . $post->path . '%')
        ->get();
        $upi_req_p = DB::table('fund_requests')->whereBetween('created_at', [$from_date,$to_date])
            ->where('status', 'Pending')
            ->where('upi', 'like', '%' . $post->path . '%')
            ->get();
        $upi_req_f = DB::table('fund_requests')->whereBetween('created_at', [$from_date,$to_date])
            ->where('status', 'Rejected')
            ->where('upi', 'like', '%' . $post->path . '%')
            ->get();
        $upi_req_all = DB::table('fund_requests')->whereBetween('created_at', [$from_date,$to_date])
        ->where('upi', 'like', '%' . $post->path . '%')
            ->get();
        ////


        $output = '';
		if ($list->count() > 0) {
            $output .= '<div class="row p-0 m-0">
                <div class="col-3 border border-success text-success" style="border-radius: 25px;padding: 10px;padding-right: 5px;padding-left: 5px;"><b>Transferred : </b> <span class="lblSuccessAmount">' . $upi_req_s->sum('amount') .'</span> (<span class="lblSuccessTxns">'.$upi_req_s->count().'</span>)</div>
                <div class="col-3 border border-warning text-warning" style="border-radius: 25px;padding: 10px;padding-right: 5px;padding-left: 5px;"><b>Pending : </b> <span class="lblPendingAmount">' . $upi_req_p->sum('amount') .'</span> (<span class="lblPendingTxns">'.$upi_req_p->count().'</span>)</div>
                <div class="col-3 border border-danger text-danger" style="border-radius: 25px;padding: 10px;padding-right: 5px;padding-left: 5px;"><b>Rejected : </b> <span class="lblFailureAmount">' . $upi_req_f->sum('amount') .'</span> (<span class="lblFailureTxns">'.$upi_req_f->count().'</span>)</div>
                <div class="col-3 border border-primary text-primary" style="border-radius: 25px;padding: 10px;padding-right: 5px;padding-left: 5px;"> <b>Total : </b> <span class="lblRefundedAmount">' . $upi_req_all->sum('amount') .'</span> (<span class="lblRefundedTxns">'.$upi_req_all->count().'</span>)</div>
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
                <th>Request Details</th>
                <th>User Details</th>
                <th>Bank Details</th>
                <th>Action Details</th>
                <th>Remark</th>
                <th>Amount</th>
                <th>Path</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>';
            $i=$start + 1;
			foreach ($list as $list) {

                $user = DB::table('users')->where('id', $list->user_id)->first();
                
                if($user){
                    $first_name = $user->first_name;
                    $middle_name = $user->middle_name;
                    $last_name = $user->last_name;
                    $outlet_name = $user->outlet_name;
                    $mobile_number = $user->mobile_number;
                }else{
                    $first_name = "";
                    $middle_name = "";
                    $last_name = "";
                    $outlet_name = "";
                    $mobile_number = "";
                }
                $bank = DB::table('banks')->where('id', $list->bank_id)->first();
                if($bank){
                    $account_name = $bank->account_name;
                    $account_number = $bank->account_number;
                    $bank_name = $bank->bank_name;
                    $account_type = $bank->account_type;
                }else{
                    $account_name = "";
                    $account_number = "";
                    $bank_name = "";
                    $account_type = "";
                }
                $by = DB::table('users')->where('id', $list->decision_by)->first();
                if($by){
                    $decision_name = $by->first_name." " .$by->middle_name." ".$by->last_name;
                }else{
                    $decision_name = "";
                }
                if($list->status == "Transferred"){
                    $bg = "success";
                    $edit_action = "--";
                }elseif ($list->status == "Rejected") {
                    $bg = "danger";
                    $edit_action = "--";
                }else{
                    $bg = "warning";
                    $edit_action = '<a id="' . $list->id . '" class="btn rounded-pill btn-info waves-effect waves-light editDetails"><i class="ri-pencil-fill align-bottom"></i>Edit</a>';
                }
                if($list->upi == 1){
                    $path = "One Gateway";
                }else if($list->upi == 2){
                    $path = "All Upi Gateway";
                }else{
                    $path = "Fund Request";
                }
				$output .= '<tr>
                <td>' . $i . '</td>
                <td>
                    Date : ' . $list->request_date . ' </br>
                    Order Id : ' . $list->order_id . ' </br>
                    UTR/Reference : ' . $list->transaction_number . ' </br>
                    Mode : ' . $list->transfer_mode . ' </br>
                    <a href="'.env('USER_HOST').''."/slip_image/".$list->slip_image.'" target="_blank" class="badge text-bg-info"><i class=" ri-file-paper-2-fill align-bottom"></i> View Slip</a>
                    
                </td>
                <td>
                    Name : '.$first_name.' '.$middle_name.' '.$last_name.' </br>
                    Outlet Name : ' . $outlet_name . ' </br>
                    Mobile No. : ' . $mobile_number . ' </br>
                </td>
                <td>
                    A/c Name : ' . $account_name . ' </br>
                    A/c : ' . $account_number . ' </br>
                    Bank : ' . $bank_name . ' </br>
                    Type : ' . $account_type . ' </br>
                    
                </td>
                <td>
                    Name : ' . $decision_name . ' </br>
                    Remark : ' . $list->decision_remark . ' </br>
                    Date : ' . $list->decision_date . ' </br>
                </td>
                <td>' . $list->remark . '</td>
                <td style="color: green;font-size: 18px;"> ₹ ' . $list->amount . '</td> 
                <td>' . $path . '</td>
                <td>
                    <span class="badge rounded-pill text-bg-' . $bg . '">' . $list->status . '</span>
                    
                </td>
                <td>

                    '.$edit_action.'
                </td>
                
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


    public function updateData(Request $post)
    {
        $rules = array(
            'edit_id'  => 'required|numeric',
            'remark' => 'required',
            'status' => 'required|in:Approved,Rejected',
        );

        $validator = \Validator::make($post->all(), array_reverse($rules));
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                $error = $value[0];
            }
            return response()->json(array(
                'type' => 'error',  
                'message' => $error
            ));
        }
        $user = DB::table('users')->where('id', Session::get('user_id'))->first();
        $report = DB::table('fund_requests')->where('id', $post->edit_id)->first();
        $isAdmin = (int) ($user->role_id ?? 0) === 1;
        if($post->status == "Approved"){
            if (!$isAdmin && (float) $user->wallet_balance < (float) $report->amount){
                return response()->json(array(
                    'type' => 'error',
                    'message' => 'Insufficient wallet balance. Available: ₹ ' . number_format((float) $user->wallet_balance, 2),
                ));
            }
            DB::beginTransaction();
            try {
                $user = DB::table('users')->where('id', Session::get('user_id'))->first();
                $by = $user->outlet_name;
                ///Report by first crediter by
                $order_id = $report->order_id;
                DB::table('reports')->insert([
                    'user_id' => $user->id,
                    'credit_user_id' => '0',
                    'debit_user_id' => $report->user_id,
                    'amount' => $report->amount,
                    'total_amount' => $report->amount,
                    'fund_type' => "Debit",
                    'transaction_type' => "Transfer Money",
                    'remark' => $post->remark,
                    'order_id' => $order_id,
                    'status' => "Success",
                    'opening_balance' => $user->wallet_balance,
                    'closing_balance' => $isAdmin ? $user->wallet_balance : $user->wallet_balance - $report->amount,
                    'transaction_date' => Carbon::now().":".rand(111,999),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
                if (!$isAdmin) {
                    DB::table('users')->where('id', $user->id)->update(['wallet_balance' => $user->wallet_balance - $report->amount]);
                }
                
                ///Report by first reciver by
                $user = DB::table('users')->where('id', $report->user_id)->first();     
                DB::table('reports')->insert([
                    'user_id' => $user->id,
                    'credit_user_id' => Session::get('user_id'),
                    'debit_user_id' => 0,
                    'amount' => $report->amount,
                    'total_amount' => $report->amount,
                    'fund_type' => "Credit",
                    'transaction_type' => "Receive Money",
                    'remark' => $post->remark,
                    'order_id' => $order_id,
                    'status' => "Success",
                    'opening_balance' => $user->wallet_balance,
                    'closing_balance' => $user->wallet_balance + $report->amount,
                    'transaction_date' => Carbon::now().":".rand(111,999),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
                $user_data = DB::table('users')->where('id', $user->id)->first(['id','first_name','mobile_number']);
                $BY = DB::table('users')->where('id', Session::get('user_id'))->first(['id','first_name','mobile_number']);
                $user = DB::table('users')->where('id', $user->id)->update(['wallet_balance' => $user->wallet_balance + $report->amount]);      
                $CURRENT_BALANCE = DB::table('users')->where('id', $user_data->id)->first();
                //////Send Sms By Cron Job Start
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable('messages') && $sms_tmp = DB::table('sms_templates')->where('slug', 'fund_receive')->first(['template_id','content','status'])) {
                        $slug = 'fund_receive';
                        $type = 'Credit';
                        $content = $sms_tmp->content;
                        $content = str_replace('{NAME}', '' . $user_data->first_name . '', $content);
                        $content = str_replace('{TYPE}', '' . $type . '', $content);
                        $content = str_replace('{AMOUNT}', '' . $report->amount . '', $content);
                        $content = str_replace('{BY}', '' . $BY->first_name . '', $content);
                        $content = str_replace('{CURRENT_BALANCE}', '' . $CURRENT_BALANCE->wallet_balance . '', $content);
                        if($sms_tmp->status == 1){
                            DB::table('messages')->insert([
                                'user_id' => 1,
                                'to_user_id' => $user_data->id,
                                'subject' => $slug,
                                'msg_source' => "SMS",
                                'template_id' => $sms_tmp->template_id,
                                'content' => $content,
                                'status' => 0,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now()
                            ]);
                        }
                    }
                } catch (\Throwable $smsError) {
                    \Log::warning('fund request sms skipped: '.$smsError->getMessage());
                }
                //////Send Sms By Cron Job End         
                ///Status Update in Fund Request Start
                $update['status'] = "Transferred";
                $update['decision_by'] = Session::get('user_id');
                $update['decision_remark'] = $post->remark;
                $update['decision_date'] = Carbon::now();
                $report = DB::table('fund_requests')->where('id', $post->edit_id)->update($update);
                ///Status Update in Fund Request End
                DB::commit();
                return response()->json(array(
                    'type' => 'success',  
                    'message' => "Fund Request Approved Successfully"
                ));
            } catch (\Exception $e) {
                DB::rollback();
                return response()->json(array(
                    'type' => 'error',  
                    'message' => "Something Went Wrong."
                ));
            }
        }else{
            $update['status'] = "Rejected";
            $update['decision_by'] = Session::get('user_id');
            $update['decision_remark'] = $post->remark;
            $update['decision_date'] = Carbon::now();
            $report = DB::table('fund_requests')->where('id', $post->edit_id)->update($update);
            return response()->json(array(
                'type' => 'success',  
                'message' => "Fund Request Rejected Successfully"
            ));
            //echo "<pre>";print_r($post->status);die;
        }
    }

    public function searchUuser(Request $post)
    {
        if($post->keyword != ''){
            $user = DB::table('users')->where('mobile_number','LIKE','%'.$post->keyword.'%')
            ->get(['id','first_name','middle_name','last_name','outlet_name','mobile_number']);
        }
        return response()->json([
            'users' => $user
        ]);
    }
}
