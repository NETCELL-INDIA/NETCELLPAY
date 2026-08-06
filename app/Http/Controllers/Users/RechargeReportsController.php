<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Session;
use Illuminate\Support\Str;
class RechargeReportsController extends Controller
{
    public function index(Request $post)
    {
        return view('users.user-reports.recharge-report');
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
                $table = "reports";
            }else{
                $table = "backup_reports";
            }
        }else{
            $from_date = Carbon::today()->format('Y-m-d')." 00:00:00";
            $to_date = Carbon::today()->format('Y-m-d')." 23:59:59";
            $table = "reports";
        } 
        $user = DB::table('users')->where("id",Session::get('user_id'))->whereNotIn("role_id",[1,2])->first();

        $query = DB::table($table)->where('user_id', Session::get('user_id'))->whereBetween('created_at', [$from_date,$to_date]);

        if($user->role_id == 6 ||$user->role_id == 3){
            $query->whereIn('transaction_type',['Recharge']);
        }else{
            $query->whereIn('transaction_type',['Commission']);
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

        if($post->order_id != ""){
            $query->where('order_id', $post->order_id);
        }

        if($post->number != ""){
            $query->where('number', $post->number);
        }
        if($post->req_order_id != ""){
            $query->where('request_order_id', $post->req_order_id);
        }
        $start= ($page-1) * $limit;
        $total_row = $query ->orderBy('id', 'DESC')->get();
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


        $list =  $query->orderBy('id', 'DESC')
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
                <th>Transaction Details</th>
                <th>Req Order Id</th>
                <th>Order Id</th>
                <th>Operator Id</th>
                <th>Status</th>
                <th>Total Amount</th>
                <th>Amount</th>
                <th>Commission/Surcharge</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>';
            $i=$start + 1;
			foreach ($list as $list) {
                if($list->status == "Success"){
                    $bg = "success";
                }elseif ($list->status == "Failed") {
                    $bg = "danger";
                }elseif ($list->status == "Refunded") {
                    $bg = "secondary";
                }else{
                    $bg = "warning";
                }
                if($list->status == "Success" && $list->complaint_id == 0){
                    $action = '<button type="submit" class="btn btn-secondary" id="receipt_btn" onclick="receiptView(`' . $list->id . '`)"><i class="ri-file-list-3-line"></i> Receipt</button>  <button type="submit" class="btn btn-warning" id="complaint_btn" onclick="complaintView(`' . $list->id . '`,`' . $list->order_id . '`)"><i class="ri-questionnaire-fill"></i> Complaint</button>';
                }else{
                    $action = '<h5>--</h5>';
                }

                $provider = DB::table('providers')->where('id', $list->provider_id)->first();
                $service = DB::table('services')->where('id', $list->service_id)->first();
                $state = DB::table('states')->where('id', DB::table($table)->where('order_id',$list->order_id)->whereIn('transaction_type',['Recharge'])->first(['state_id'])->state_id)->first(['state_name']);
                if($state){
                    $state = $state->state_name;
                }else{
                    $state = "No State";
                }
				$output .= '<tr>
                <td>' . $i . '</td>
                <td>
                    ' . $list->transaction_date . ' </br>
                    Number : ' . $list->number . ' </br>
                    ' . Str::of($provider->provider_name)->upper() . 
                    ' - ' . Str::of($state)->upper() . 
                    ' - ' . Str::of($service->service_name)->upper() . 
                    ' - ' . Str::of($list->path)->upper() . '</br>
                </td>
                <td>' . $list->request_order_id . '</td>
                <td>' . $list->order_id . '</td>
                <td>' . $list->operator_id . '</td>
                <td>
                    <span class="badge rounded-pill text-bg-' . $bg . '">' . $list->status . '</span>
                </td>
                <td style="font-size: 18px;"> ₹ ' . $list->total_amount . '</td> 
                <td style="font-size: 18px;"> ₹ ' . DB::table($table)->where('order_id',$list->order_id)->whereIn('transaction_type',['Recharge'])->first(['amount'])->amount . '</td> 
                <td style="font-size: 18px;"> ₹ ' . $list->commission . '</td> 
                <td>
                ' . $action .'
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

}
