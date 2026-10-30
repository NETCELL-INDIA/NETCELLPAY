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
        $w2rStatus = [];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('wrong_recharge_requests') && $list_count > 0) {
                $w2rStatus = DB::table('wrong_recharge_requests')
                    ->whereIn('report_id', $list->pluck('id'))
                    ->whereIn('status', ['Pending', 'Approved', 'Refunded'])
                    ->pluck('status', 'report_id')
                    ->all();
            }
        } catch (\Throwable $e) {
        }
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
                if($list->status == "Success" && $list->complaint_id == 0 && \helpers::reportAllowsComplaint($list)){
                    $action = '<button type="submit" class="btn btn-secondary" id="receipt_btn" onclick="receiptView(`' . $list->id . '`)"><i class="ri-file-list-3-line"></i> Receipt</button>  <button type="submit" class="btn btn-warning" id="complaint_btn" onclick="complaintView(`' . $list->id . '`,`' . $list->order_id . '`)"><i class="ri-questionnaire-fill"></i> Complaint</button>';
                }else{
                    $action = '<h5>--</h5>';
                }
                if (in_array($list->transaction_type ?? '', ['Recharge', 'Bill Pay'], true)) {
                    $action .= '<div class="mt-1"><a class="btn btn-sm btn-outline-primary" href="'.e(url('users/user-reports/recharge-receipt/'.$list->id)).'">PDF</a></div>';
                }
                if (($list->transaction_type ?? '') === 'Recharge' && $list->status === 'Success') {
                    if (isset($w2rStatus[$list->id])) {
                        $action .= '<div class="mt-1"><span class="badge bg-info">Wrong no. '.e($w2rStatus[$list->id]).'</span></div>';
                    } else {
                        $action .= '<div class="mt-1"><button type="button" class="btn btn-sm btn-danger btn-wrong-no" data-id="'.e($list->id).'" data-order="'.e($list->order_id).'" data-number="'.e($list->number).'">Wrong number</button></div>';
                    }
                }

                $provider = DB::table('providers')->where('id', $list->provider_id)->first();
                $service = DB::table('services')->where('id', $list->service_id)->first();
                $reportRow = DB::table($table)->where('order_id', $list->order_id)->whereIn('transaction_type', ['Recharge'])->first(['state_id', 'amount']);
                $state = $reportRow && $reportRow->state_id
                    ? DB::table('states')->where('id', $reportRow->state_id)->first(['state_name'])
                    : null;
                if($state){
                    $state = $state->state_name;
                }else{
                    $state = "No State";
                }
				$output .= '<tr>
                <td>' . e($list->id) . '</td>
                <td>
                    ' . $list->transaction_date . ' </br>
                    Number : ' . $list->number . ' </br>
                    ' . Str::of($provider->provider_name ?? '-')->upper() . 
                    ' - ' . Str::of($state)->upper() . 
                    ' - ' . Str::of($service->service_name ?? '-')->upper() . 
                    ' - ' . Str::of($list->path)->upper() . '</br>
                </td>
                <td>' . $list->request_order_id . '</td>
                <td>' . $list->order_id . '</td>
                <td>' . $list->operator_id . '</td>
                <td>
                    ' . report_status_html($list->status, $list->id) . '
                </td>
                <td style="font-size: 18px;"> ₹ ' . $list->total_amount . '</td> 
                <td style="font-size: 18px;"> ₹ ' . ($reportRow->amount ?? $list->amount) . '</td> 
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

    public function download(Request $post)
    {
        $user = DB::table('users')->where('id', Session::get('user_id'))->whereNotIn('role_id', [1, 2])->first();
        abort_unless($user, 403);

        $today = Carbon::today()->format('Y-m-d');
        $from = (string) $post->get('from_date');
        $to = (string) $post->get('to_date');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = $today;
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = $from;
        }
        $table = ((string) $post->get('tbl_type') === '1' && \Illuminate\Support\Facades\Schema::hasTable('backup_reports'))
            ? 'backup_reports'
            : 'reports';

        $query = DB::table($table)
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);

        if ((int) $user->role_id === 6 || (int) $user->role_id === 3) {
            $query->whereIn('transaction_type', ['Recharge']);
        } else {
            $query->whereIn('transaction_type', ['Commission']);
        }
        if (trim((string) $post->get('order_id')) !== '') {
            $query->where('order_id', trim((string) $post->get('order_id')));
        }
        if (trim((string) $post->get('req_order_id')) !== '') {
            $query->where('request_order_id', trim((string) $post->get('req_order_id')));
        }
        if (trim((string) $post->get('number')) !== '') {
            $query->where('number', trim((string) $post->get('number')));
        }

        $rows = $query->orderByDesc('id')->limit(4000)->get([
            'created_at', 'number', 'request_order_id', 'order_id', 'operator_id',
            'status', 'total_amount', 'amount', 'commission', 'transaction_type',
        ]);

        $csv = "date,number,request_order_id,order_id,operator_id,status,total_amount,amount,commission,type\n";
        foreach ($rows as $row) {
            $line = [
                (string) $row->created_at,
                (string) $row->number,
                (string) $row->request_order_id,
                (string) $row->order_id,
                (string) $row->operator_id,
                (string) $row->status,
                (string) $row->total_amount,
                (string) $row->amount,
                (string) $row->commission,
                (string) $row->transaction_type,
            ];
            $csv .= implode(',', array_map(function ($value) {
                $value = str_replace(["\r", "\n"], ' ', $value);
                if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
                    $value = "'".$value;
                }

                return '"'.str_replace('"', '""', $value).'"';
            }, $line))."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="recharge-report-'.$from.'-to-'.$to.'.csv"',
        ]);
    }

}
