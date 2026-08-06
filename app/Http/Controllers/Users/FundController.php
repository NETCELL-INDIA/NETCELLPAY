<?php

namespace App\Http\Controllers\Users;

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
        return view('users.fund.fund-request');
    }

    public function fetchAll(Request $post)
    {

        $rules = array(
            'page' => 'required|numeric',
            'limit' => 'required|numeric',
        );

        $validator = \Validator::make($post->all(), array_reverse($rules));
        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $value) {
                $error = $value[0];
            }
            return '<h4 class="text-center text-secondary my-3">'.$error.'</h4>';
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
        $start= ($page-1) * $limit;
        $total_row = DB::table($table)->where('user_id', Session::get('user_id'))
        ->whereBetween('created_at', [$from_date,$to_date])
        ->orderBy('id', 'DESC')->get();
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
        $list = DB::table($table)->where('user_id', Session::get('user_id'))
        ->whereBetween('created_at', [$from_date,$to_date])
        ->orderBy('id', 'DESC')
        ->offset($start)->limit($limit)->get();
        //echo "<pre>";print_r($list);die;
        $list_count = $list->count();
        $output = '';
		if ($list->count() > 0) {
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
                <th>Status</th>
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
                }elseif ($list->status == "Rejected") {
                    $bg = "danger";
                }else{
                    $bg = "warning";
                }
				$output .= '<tr>
                <td>' . $i . '</td>
                <td>
                    Date : ' . $list->request_date . ' </br>
                    Order Id : ' . $list->order_id . ' </br>
                    UTR/Reference : ' . $list->transaction_number . ' </br>
                    Mode : ' . $list->transfer_mode . ' </br>
                    <a href="'.asset("slip_image/".$list->slip_image).'" target="_blank" class="badge text-bg-info"><i class=" ri-file-paper-2-fill align-bottom"></i> View Slip</a>
                    
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
                <td>
                    <span class="badge rounded-pill text-bg-' . $bg . '">' . $list->status . '</span>
                    
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

    public function fundRequestSubmit(Request $post)
    {

        $rules = array(
            'bank_id'  => 'required|numeric',
            'transfer_mode'  => 'required',
            'amount'  => 'required|numeric|min:1',
            'transaction_number'  => 'required',
            'remark'  => 'required',
            'slip_image' => 'mimes:jpeg,jpg,png|max:10000',
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

        if($post->slip_image){
            $slip_image = csrf_token().time().'.'.$post->slip_image->extension();  
            $post->slip_image->move(public_path('slip_image'), $slip_image);
        }else{
            $slip_image = "slip_image.png";
        }
        $user = DB::table('users')->where('id',Session::get('user_id'))->first();
        $update = DB::table('fund_requests')->insert([
            'user_id' => Session::get('user_id'),
            'request_to' => $user->parent_id,
            'bank_id' => $post->bank_id,
            'amount' => $post->amount,
            'request_date' => Carbon::now(),
            'status' => "Pending",
            'transfer_mode' => $post->transfer_mode,
            'transaction_number' => $post->transaction_number,
            'remark' => $post->remark,
            'slip_image' => $slip_image,
            'order_id' => "FND".date("YmdHis").rand(11111, 999999),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        if($update){
            $data['type'] = 'success';
            $data['message'] =  "Fund request submit sucessfuly.";
        }else{
            $data['type'] = 'error';
            $data['message'] = "Something went wrong!";
        }
        return $data;
        //echo "<pre>";print_r($post->all());die;
    }

    public function fundRequestBankList(Request $post)
    {
        $user = DB::table('users')->where('id',Session::get('user_id'))->first();
        if($user->status != 1){
            return response()->json(array(
                'type' => 'error',  
                'message' => "Account Not Active.Contact To Admin"
            )); 
        }
        
        //echo "<pre>";print_r($user->parent_id);die;
        $bank = DB::table('banks')->where('user_id', $user->parent_id)->where('deleted_at', '!=' , 1)->orderBy('id', 'DESC')->get();
        if($bank){
            $data['type'] = 'success';
            $data['message'] = "Fatch sucessfuly";
            $data['banks'] = $bank;
        } else {
            $data['type'] = 'error';
            $data['message'] = "Something went wrong!";
        }
        return $data;
    }
}
