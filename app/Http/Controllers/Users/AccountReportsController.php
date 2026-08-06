<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Session;
class AccountReportsController extends Controller
{
    public function index(Request $post)
    {
        return view('users.user-reports.account-report');
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
        $total_row = DB::table($table)
        ->where('user_id', Session::get('user_id'))->whereBetween('created_at', [$from_date,$to_date])
        ->where('order_id', 'like', '%' . $post->order_id . '%')
        ->where('transaction_type', 'like', '%' . $post->tr_type . '%')
        ->where('fund_type', 'like', '%' . $post->fund_type . '%')
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
        
        
        $list = DB::table($table)
        ->where('user_id', Session::get('user_id'))->whereBetween('created_at', [$from_date,$to_date])
        ->where('order_id', 'like', '%' . $post->order_id . '%')
        ->where('transaction_type', 'like', '%' . $post->tr_type . '%')
        ->where('fund_type', 'like', '%' . $post->fund_type . '%')
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
                <th scope="col">ID</th>
                <th scope="col">Date & Time</th>
                <th scope="col">Order Id</th>
                <th scope="col">Tr Type</th>
                <th scope="col">Credit By</th>
                <th scope="col">Debit By</th>
                <th scope="col">Remark</th>
                <th scope="col">Total Amount</th>
                <th scope="col">Amount</th>
                <th scope="col">Credit/Debit</th>
                <th scope="col">Opening</th>
                <th scope="col">Closing</th>
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
                <td style="font-size: 18px;"> ₹ ' . $list->total_amount . '</td> 
                <td style="color: '.$inr.';font-size: 18px;"> ₹ ' . $list->amount . '</td> 
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

}
