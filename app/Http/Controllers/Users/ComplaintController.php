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
class ComplaintController extends Controller
{
    public function index(Request $post)
    {
        return view('users.support.complaint');
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
                $table = "complaints";
            }else{
                $table = "backup_complaints";
            }
        }else{
            $from_date = Carbon::today()->format('Y-m-d')." 00:00:00";
            $to_date = Carbon::today()->format('Y-m-d')." 23:59:59";
            $table = "complaints";
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
        
        $list = DB::table($table)
        ->where('user_id', Session::get('user_id'))->whereBetween('created_at', [$from_date,$to_date])
        //->where('complaint_id',"!=",0)
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
                    <th>Request ID</th>
                    <th>Date & Time</th>
                    <th>Service</th>
                    <th>Transaction Details</th>
                    <th>Subject</th>
                    <th>Action Details</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';
            $i=1;
			foreach ($list as $list) {
                $service = DB::table('services')->where('id', $list->service_id)->first();
                $action_user = DB::table('users')->where('id', $list->decision_by)->first();
                if($action_user){
                    $d_first_name = $action_user->first_name;
                    $d_middle_name = $action_user->middle_name;
                    $d_last_name = $action_user->last_name;
                    $d_outlet_name = $action_user->outlet_name;
                    $d_mobile_number = $action_user->mobile_number;
                }else{
                    $d_first_name = "-";
                    $d_middle_name = "";
                    $d_last_name = "";
                    $d_outlet_name = "-";
                    $d_mobile_number = "-";
                }

                if($list->status == "Sloved"){
                    $bg = "success";
                }elseif ($list->status == "Closed") {
                    $bg = "danger";
                }elseif ($list->status == "Open") {
                    $bg = "warning";    
                }else{
                    $bg = "primary";
                }
				$output .= '<tr>
                    <td>' . $i . '</td>
                    <td>' . $list->request_id . '</td>
                    <td>' . $list->created_at . '</td>
                    <td>' . Str::of($service->service_name)->upper() . '</td>
                    <td><button type="submit" class="btn btn-secondary" id="report_view_btn" onclick="reportsView(`' . $list->report_id . '`)"><i class="ri-file-list-3-line"></i> Check Transaction</button></td>
                    <td>' . $list->subject . '</td>
                    <td>
                        Name : '.$d_first_name.' '.$d_middle_name.' '.$d_last_name.' </br>
                        Date : ' . $list->decision_date . ' </br>
                        Remark : ' . $list->decision_remark . ' </br>
                    </td>
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

    public function getReport(Request $post){
        $report = $list = DB::table('reports')
        ->where('user_id', Session::get('user_id'))
        ->where('id', $post->id)
        ->first();
        $output = '';
        if($report){
            $provider = DB::table('providers')->where('id', $list->provider_id)->first();
            $service = DB::table('services')->where('id', $list->service_id)->first();
            if($report->status == "Success"){
                $table_bg = "success";
                $bg = "success";
            }elseif ($report->status == "Failed") {
                $table_bg = "danger";
                $bg = "danger";
            }elseif ($report->status == "Refunded") {
                $table_bg = "secondary";
                $bg = "secondary";
            }else{
                $table_bg = "warning";
                $bg = "warning";
            }

            //$table_bg = 'success';
            // $table_bg = 'info';
            // $table_bg = 'danger';
            // $table_bg = 'warning';
            $output = '<table class="table table-nowrap">
                <tbody>
                    <tr class="table-'.$table_bg.'">
                        <td>Date & Time : </td>
                        <td>'.$report->transaction_date.'</td>
                    </tr>
                    <tr class="table-'.$table_bg.'">
                        <td>Number : </td>
                        <td>'.$report->number.'</td>
                    </tr>
                    <tr class="table-'.$table_bg.'">
                        <td>Provider : </td>
                        <td>' . Str::of($provider->provider_name)->upper() . 
                        ' - ' . Str::of($service->service_name)->upper() . 
                        ' - ' . Str::of($list->path)->upper() . '</td>
                    </tr>
                    <tr class="table-'.$table_bg.'">
                        <td>Order Id : </td>
                        <td>'.$report->order_id.'</td>
                    </tr>
                    <tr class="table-'.$table_bg.'">
                        <td>Operator Id : </td>
                        <td>'.$report->operator_id.'</td>
                    </tr>
                    <tr class="table-'.$table_bg.'">
                        <td>Status : </td>
                        <td>
                            <span class="badge rounded-pill text-bg-' . $bg . '">' . $report->status . '</span>
                        </td>
                    </tr>
                    <tr class="table-'.$table_bg.'">
                        <td>Total Amount : </td>
                        <td> ₹ '.$report->total_amount.'</td>
                    </tr>
                    <tr class="table-'.$table_bg.'">
                        <td>Amount : </td>
                        <td> ₹ '.$report->amount.'</td>
                    </tr>
                    <tr class="table-'.$table_bg.'">
                        <td>Commission/Surcharge : </td>
                        <td> ₹ '.$report->commission.'</td>
                    </tr>
                    
                </tbody>
            </table>';
        }else{
            $output = '<h4 class="text-center text-secondary my-3">No Data found</h4>';
        }

        return $output;
    }

}
