<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Redirect;
use App\Common;
use Validator;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Session;

use Illuminate\Support\Str;

use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RechargeReportsController extends Controller

{

    public function index(Request $post)
    {
        $apis = DB::table('apis')->orderBy('api_name')->get(['id', 'api_name']);
        $services = DB::table('services')->orderBy('service_name')->get(['id', 'service_name']);
        $providers = DB::table('providers')->orderBy('provider_name')->get(['id', 'provider_name', 'service_id']);
        $circles = DB::table('states')->orderBy('state_name')->get(['id', 'state_name']);

        return view('admin.user-reports.recharge-report', compact('apis', 'services', 'providers', 'circles'));
    }

    private function rechargeReportBaseQuery(Request $post)
    {
        $table = ((int) ($post->tbl_type ?? 0) === 1 && Schema::hasTable('backup_reports')) ? 'backup_reports' : 'reports';

        $q = DB::table($table . ' as r')
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->leftJoin('providers as p', 'p.id', '=', 'r.provider_id')
            ->leftJoin('services as s', 's.id', '=', 'r.service_id')
            ->leftJoin('apis as a', 'a.id', '=', 'r.api_id')
            ->leftJoin('states as st', 'st.id', '=', 'r.state_id')
            ->whereIn('r.transaction_type', ['Recharge', 'Bill Pay', 'Bill Payment']);

        $from = $post->from_date ?: Carbon::today()->format('Y-m-d');
        $to = $post->to_date ?: Carbon::today()->format('Y-m-d');
        $q->where(function ($w) use ($from, $to) {
            $w->whereBetween('r.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->orWhereBetween('r.transaction_date', [$from . ' 00:00:00', $to . ' 23:59:59']);
        });

        if ($post->api_id) {
            $q->where('r.api_id', (int) $post->api_id);
        }
        if ($post->service_id) {
            $q->where('r.service_id', (int) $post->service_id);
        }
        if ($post->provider_id) {
            $q->where('r.provider_id', (int) $post->provider_id);
        }
        if ($post->circle_id) {
            $q->where('r.state_id', (int) $post->circle_id);
        }
        if ($post->status && $post->status !== 'All') {
            $status = $post->status;
            if ($status === 'Failure' || $status === 'Failed') {
                $q->whereIn('r.status', ['Failed', 'Failure']);
            } elseif ($status === 'Refunded') {
                $q->whereIn('r.status', ['Refunded', 'Refund']);
            } else {
                $q->where('r.status', $status);
            }
        }
        if ($post->amount !== null && $post->amount !== '') {
            $q->where('r.amount', $post->amount);
        }
        if ($post->mode && $post->mode !== 'All') {
            if (Schema::hasColumn($table, 'path')) {
                $q->where('r.path', 'like', '%' . $post->mode . '%');
            } elseif ($post->mode !== '') {
                $q->where('r.fund_type', 'like', '%' . $post->mode . '%');
            }
        }
        if ($post->search_text) {
            $term = trim($post->search_text);
            $q->where(function ($w) use ($term) {
                $w->where('r.number', 'like', "%{$term}%")
                    ->orWhere('r.order_id', 'like', "%{$term}%")
                    ->orWhere('r.request_order_id', 'like', "%{$term}%")
                    ->orWhere('r.operator_id', 'like', "%{$term}%")
                    ->orWhere('r.id', $term);
            });
        }
        if ($post->user_id) {
            $userId = (int) $post->user_id;
            if ($post->include_child == 1 || $post->include_child === '1' || $post->include_child === true) {
                $childIds = DB::table('users')->where('parent_id', $userId)->pluck('id')->toArray();
                $ids = array_merge([$userId], $childIds);
                $q->whereIn('r.user_id', $ids);
            } else {
                $q->where('r.user_id', $userId);
            }
        }
        if (Schema::hasColumn($table, 'complaint_id') && (int) ($post->complaint_id ?? 0) === 1) {
            $q->where('r.complaint_id', '!=', 0);
        }

        return $q;
    }

    public function fetchAllModern(Request $post)
    {
        $limit = (int) ($post->show ?: 10);
        if (!in_array($limit, [10, 25, 50, 100], true)) {
            $limit = 10;
        }
        $page = max(1, (int) ($post->page ?: 1));
        $offset = ($page - 1) * $limit;

        $base = $this->rechargeReportBaseQuery($post);
        $total = (clone $base)->count();

        $summaryRows = (clone $base)
            ->selectRaw("
                SUM(CASE WHEN r.status = 'Success' THEN r.amount ELSE 0 END) as success_amt,
                SUM(CASE WHEN r.status = 'Success' THEN 1 ELSE 0 END) as success_cnt,
                SUM(CASE WHEN r.status IN ('Pending','Under Proces','Under Process','Processing') THEN r.amount ELSE 0 END) as pending_amt,
                SUM(CASE WHEN r.status IN ('Pending','Under Proces','Under Process','Processing') THEN 1 ELSE 0 END) as pending_cnt,
                SUM(CASE WHEN r.status IN ('Failed','Failure') THEN r.amount ELSE 0 END) as failure_amt,
                SUM(CASE WHEN r.status IN ('Failed','Failure') THEN 1 ELSE 0 END) as failure_cnt,
                SUM(CASE WHEN r.status IN ('Refunded','Refund') THEN r.amount ELSE 0 END) as refunded_amt,
                SUM(CASE WHEN r.status IN ('Refunded','Refund') THEN 1 ELSE 0 END) as refunded_cnt
            ")
            ->first();

        $reports = (clone $base)
            ->select(
                'r.*',
                'u.outlet_name',
                'u.first_name',
                'u.mobile_number',
                'p.provider_name',
                's.service_name',
                'a.api_name',
                'st.state_name as circle_name'
            )
            ->orderByDesc('r.id')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $table = ((int) ($post->tbl_type ?? 0) === 1 && Schema::hasTable('backup_reports')) ? 'backup_reports' : 'reports';
        $hasPath = Schema::hasColumn($table, 'path');
        $hasComplaint = Schema::hasColumn($table, 'complaint_id');

        $rows = '';
        if ($reports->count() > 0) {
            foreach ($reports as $list) {
                $status = $list->status ?: '-';
                $userName = e($list->outlet_name ?: $list->first_name ?: 'User');
                $userMobile = e($list->mobile_number ?: '-');
                $userId = e($list->user_id ?: '-');
                $dt = e($list->transaction_date ?: $list->created_at);
                $mode = e(strtoupper((string) ($hasPath ? ($list->path ?: '-') : ($list->fund_type ?: 'WEB'))));
                $opId = e($list->operator_id ?: '-');
                $reqId = e($list->request_order_id ?: '-');
                $st = e($status);
                $orderId = e($list->order_id ?: ('R' . $list->id));

                $action = '<div class="recharge-action-btns">'
                    . '<a href="javascript:void(0)" class="btn btn-soft-primary" title="Edit Status" onclick="editStatus(\'' . e($list->id) . '\',\'' . $st . '\',\'' . e($list->operator_id ?: '') . '\')"><i class="ri-pencil-line"></i></a>'
                    . '<a href="javascript:void(0)" id="' . e($list->order_id) . '" class="btn btn-soft-info checkApilog" title="API Log"><i class="ri-file-list-line"></i></a>';

                if ($hasComplaint && !empty($list->complaint_id)) {
                    $action .= '<a id="' . e($list->complaint_id) . '" class="btn btn-soft-danger editComplaint" title="Complaint"><i class="ri-customer-service-2-line"></i></a>';
                }

                $action .= '</div>';

                $rows .= '<tr>
                    <td><span class="recharge-order-id">' . $orderId . '</span><span class="recharge-row-id">#' . e($list->id) . '</span></td>
                    <td class="recharge-date">' . $dt . '</td>
                    <td class="recharge-user">
                        <span class="recharge-user-name">' . $userName . '</span>
                        <span class="recharge-user-meta">' . $userMobile . ' · ID:' . $userId . '</span>
                    </td>
                    <td>' . e($list->provider_name ?: '-') . '</td>
                    <td>' . e($list->circle_name ?: '-') . '</td>
                    <td class="recharge-number">' . e($list->number ?: '-') . '</td>
                    <td class="text-end recharge-amount">₹' . number_format((float) $list->amount, 2) . '</td>
                    <td>' . report_status_html($status, $list->id) . '</td>
                    <td>' . e($list->api_name ?: '-') . '</td>
                    <td class="recharge-ids"><span>' . $opId . '</span><span>' . $reqId . '</span></td>
                    <td>' . $mode . '</td>
                    <td>' . $action . '</td>
                </tr>';
            }
        } else {
            $rows = '<tr><td colspan="12" class="text-center text-muted py-4">No data available in table</td></tr>';
        }

        $fromEntry = $total ? ($offset + 1) : 0;
        $toEntry = min($offset + $limit, $total);

        return response()->json([
            'type' => 'success',
            'rows' => $rows,
            'summary' => [
                'success_amt' => number_format((float) ($summaryRows->success_amt ?? 0), 2),
                'success_cnt' => (int) ($summaryRows->success_cnt ?? 0),
                'pending_amt' => number_format((float) ($summaryRows->pending_amt ?? 0), 2),
                'pending_cnt' => (int) ($summaryRows->pending_cnt ?? 0),
                'failure_amt' => number_format((float) ($summaryRows->failure_amt ?? 0), 2),
                'failure_cnt' => (int) ($summaryRows->failure_cnt ?? 0),
                'refunded_amt' => number_format((float) ($summaryRows->refunded_amt ?? 0), 2),
                'refunded_cnt' => (int) ($summaryRows->refunded_cnt ?? 0),
            ],
            'pagination' => [
                'page' => $page,
                'show' => $limit,
                'total' => $total,
                'from' => $fromEntry,
                'to' => $toEntry,
                'last_page' => max(1, (int) ceil($total / max($limit, 1))),
            ],
        ]);
    }

    public function downloadModern(Request $post)
    {
        $reports = $this->rechargeReportBaseQuery($post)
            ->select(
                'r.*',
                'u.outlet_name',
                'u.first_name',
                'u.mobile_number',
                'p.provider_name',
                'a.api_name',
                'st.state_name as circle_name'
            )
            ->orderByDesc('r.id')
            ->limit(5000)
            ->get();

        $filename = 'recharge-report-' . date('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($reports) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Recharge ID', 'Date Time', 'User', 'Operator', 'Circle', 'Number', 'Amount', 'Status', 'API', 'Operator ID', 'Mode']);
            foreach ($reports as $list) {
                fputcsv($out, [
                    $list->order_id ?: ('R' . $list->id),
                    $list->transaction_date ?: $list->created_at,
                    trim(($list->outlet_name ?: $list->first_name ?: 'User') . ' / ' . ($list->mobile_number ?: '')),
                    $list->provider_name,
                    $list->circle_name,
                    $list->number,
                    $list->amount,
                    $list->status,
                    $list->api_name,
                    $list->operator_id,
                    $list->fund_type ?: 'WEB',
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function fetchAll(Request $post)

    {

        $rules = array(

            'page' => 'required|numeric',

            'limit' => 'required|numeric',

            'tbl_type' => 'required|numeric',

            'service_id' => 'required|numeric',

            'provider_id' => 'required|numeric',

            'api_id' => 'required|numeric',

            'status' => 'required',

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



        if($post->status =="All"){

            $post->status = '';

        }



        if($post->service_id == 0){

            $post->service_id = '';

        }



        if($post->provider_id == 0){

            $post->provider_id = '';

        }



        if($post->api_id == 0){

            $post->api_id = '';

        }


        if($post->complaint_id != 0){
            $complaint_id = [['complaint_id', '!=', 0]];
        }else{
            $complaint_id = [['status', 'like', '%' . '' . '%']];
        }



        // 'service_id' => 'required|numeric',

        //     'provider_id' => 'required|numeric',

        //     'api_id' => 'required|numeric',

        //     'status' => 'required',



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

            ->where('number', 'like', '%' . $post->number . '%')

            ->where('service_id', 'like', '%' . $post->service_id . '%')

            ->where('provider_id', 'like', '%' . $post->provider_id . '%')

            ->where('api_id', 'like', '%' . $post->api_id . '%')

            ->where('status', 'like', '%' . $post->status . '%')
            
            ->where($complaint_id)

            ->where('transaction_type', ['Recharge'])

            //->where('transaction_type', $post->tr_type)

            //->whereNotIn('transaction_type',['Commission','Recharge','Reverse Commission','Refund','Money Transfer'])

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

            ->where('number', 'like', '%' . $post->number . '%')

            ->where('service_id', 'like', '%' . $post->service_id . '%')

            ->where('provider_id', 'like', '%' . $post->provider_id . '%')

            ->where('api_id', 'like', '%' . $post->api_id . '%')

            ->where('status', 'like', '%' . $post->status . '%')

            ->where($complaint_id)

            ->where('transaction_type', ['Recharge'])

            ->orderBy('id', 'DESC')

            ->offset($start)

            ->limit($limit)

            ->get();

        $list_count = $list->count();

        $report_s = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
        ->where('transaction_type', 'Recharge')
        ->where('status', 'Success')
        ->get();
        $report_p = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
            ->where('transaction_type', 'Recharge')
            ->where('status', 'Pending')
            ->get();
        $report_f = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
            ->where('transaction_type', 'Recharge')
            ->where('status', 'Failed')
            ->get();
        $report_r = DB::table('reports')->whereBetween('created_at', [$from_date,$to_date])
            ->where('transaction_type', 'Refund')
            ->where('status', 'Success')
            ->get();

        //$success_count = DB::table($table)->whereBetween('created_at', [$from_date,$to_date])->get()->count();

        //$success_amount = 0;

        $output = '';

		if ($list->count() > 0) {

			$output .= '<div class="row p-0 m-0">
                <div class="col-3 border border-success text-success" style="border-radius: 25px;padding: 10px;padding-right: 5px;padding-left: 5px;"><b>Success : </b> <span class="lblSuccessAmount">' . $report_s->sum('total_amount') .'</span> (<span class="lblSuccessTxns">'.$report_s->count().'</span>)</div>
                <div class="col-3 border border-warning text-warning" style="border-radius: 25px;padding: 10px;padding-right: 5px;padding-left: 5px;"><b>Pending : </b> <span class="lblPendingAmount">' . $report_p->sum('total_amount') .'</span> (<span class="lblPendingTxns">'.$report_p->count().'</span>)</div>
                <div class="col-3 border border-danger text-danger" style="border-radius: 25px;padding: 10px;padding-right: 5px;padding-left: 5px;"><b>Failure : </b> <span class="lblFailureAmount">' . $report_f->sum('total_amount') .'</span> (<span class="lblFailureTxns">'.$report_f->count().'</span>)</div>
                <div class="col-3 border border-primary text-primary" style="border-radius: 25px;padding: 10px;padding-right: 5px;padding-left: 5px;"> <b>Refunded : </b> <span class="lblRefundedAmount">' . $report_r->sum('total_amount') .'</span> (<span class="lblRefundedTxns">'.$report_r->count().'</span>)</div>
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

                <th>Trasaction Details</th>

                <th>Amount</th>

                <th>Total Amount</th>

                <th>Operator Id</th>

                <th>Status</th>

                <th>Commission</th>

                <th>Charges</th>

                <th>Complaint</th>

                <th>Opening</th>

                <th>Closing</th>

                <th>Action</th>

              </tr>

            </thead>

            <tbody>';

            $i=$start + 1;

			foreach ($list as $list) {

                $provider = DB::table('providers')->where('id', $list->provider_id)->first(['provider_name']);



                $service = DB::table('services')->where('id', $list->service_id)->first(['service_name']);
                $state = DB::table('states')->where('id', $list->state_id)->first(['state_name']);
                if($state){
                    $state = $state->state_name;
                }else{
                    $state = "No State";
                }

                $api = DB::table('apis')->where('id', $list->api_id)->first(['api_name'])->api_name;

                //$b_api = DB::table('apis')->where('id', $list->api_id)->first(['api_name'])->api_name;

                if($list->complaint_id == 0){

                    $complaint  = 'NO';

                    $complaint_bg  = 'info';

                    $complaint_text  = '';

                }else{

                    $complaint  = 'YES';

                    $complaint_bg  = 'danger';

                    $complaint_text = '<a id="' . $list->complaint_id . '" class="badge text-bg-secondary editComplaint"><i class="ri-pencil-fill align-bottom"></i> Edit</a>';

                }

                // if(!$b_api){

                //     $b_api = "NO API";

                // }

                if(!$api){

                    $api = "NO API";

                }

                if(!$service){

                    $service = "NO SERVICE";

                }

                if(!$provider){

                    $provider = "NO provider";

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



                if($list->status == "Success"){

                    $bg = "success";

                    $inr = "green";

                }elseif ($list->status == "Failed") {

                    $bg = "danger";

                    $inr = "red";

                }else if ($list->status == "Pending"){

                    $bg = "warning";

                    $inr = "black";

                }else if ($list->status == "Refunded"){

                    $bg = "secondary";

                    $inr = "blue";

                }else if ($list->status == "Under Process"){

                    $bg = "primary";

                    $inr = "blue";        

                }else{

                    $bg = "dark";

                    $inr = "black";

                }

				$output .= '<tr>

                <td>' . $i . '</td>

                <td>

                    Name : '.$user_dt_first_name.' '.$user_dt_middle_name.' '.$user_dt_last_name.' </br>

                    Outlet Name : ' . $user_dt_outlet_name . ' </br>

                    Mobile No. : ' . $user_dt_mobile_number . ' </br>

                </td>

                <td style="text-transform: uppercase;">

                    Req Order Id : '.$list->request_order_id.' </br>

                    Order Id : '.$list->order_id.' </br>

                    Date/Time : '.$list->transaction_date.' </br>

                    Number : ' . $list->number . ' </br>

                    ' . $provider->provider_name . ' - ' . $state . '

                    - ' . $service->service_name . ' -  '.$list->path.' - ('.$list->ip_address.')</br>

                    API : ' . $api . ' 

                </td>

                <td style="color: '.$inr.';font-size: 18px;"> ₹ ' . round($list->amount, 2) . '</td> 

                <td style="color: '.$inr.';font-size: 18px;"> ₹ ' . round($list->total_amount, 2) . '</td> 

                <td>

                    <input onchange="changeOperatorId('.$list->id.')" id="input_operator_id_'.$list->id.'" class="form-control" type="text" value="' . $list->operator_id . '" >

                </td>

                <td>

                    ' . report_status_html($list->status, $list->id) . '

                </td>

                <td style="text-transform: uppercase;">

                    USER : ₹ '.round($list->commission, 2).' </br>

                    DT : ₹ '.round($list->dt_commission, 2).' </br>

                    MD : ₹ ' . round($list->md_commission, 2) . ' </br>

                    WT : ₹ ' . round($list->wt_commission, 2) . ' </br>

                </td>

                <td style="text-transform: uppercase;">

                    USER : ₹ '.round($list->charges, 2).' </br>

                </td>

                <td>

                    <span class="badge rounded-pill text-bg-'.$complaint_bg.'">' . $complaint . '</span>

                    ' . $complaint_text . '

                    

                </td>

                

                <td> ₹ ' . $list->opening_balance . '</td>

                <td> ₹ ' . $list->closing_balance . '</td>

                <td>

                    <a id="' . $list->id . '" class="badge text-bg-secondary"  onclick="editStatus(`' . $list->id . '`,`' . $list->status . '`,`' . $list->operator_id . '`)"><i class="ri-pencil-fill align-bottom"></i> Edit</a>

                    <a id="' . $list->order_id . '" class="badge text-bg-info checkApilog"><i class="ri-file-list-line align-bottom"></i> Api Log</a>

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

  

  

  

  	public function fetchAllExport(Request $post)

    {

        // Define the name of the output file

        $fileName = rand(100000,9999999).'.csv';


        if($post->user_id ==0){

            $post->user_id = '';

        }



        if($post->status =="All"){

            $post->status = '';

        }



        if($post->service_id == 0){

            $post->service_id = '';

        }



        if($post->provider_id == 0){

            $post->provider_id = '';

        }



        if($post->api_id == 0){

            $post->api_id = '';

        }

        if($post->from_date){

            $from_date = $post->from_date." 00:00:00";

            $to_date = $post->to_date." 23:59:59";

            if($post->tbl_type == 0){

                $table = "reports";

            }else{

                $table = "backup_reports";

            }
        }


        $response = new StreamedResponse(function() use ($fileName,$post,$from_date,$to_date,$table) {
            $handle = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($handle, [
                'Full Name',
                'Outlet Name',
                'Outlet No',
                'User Id',
                'Req Order Id',
                'Order Id',
                'Date/Time',
                'Number',
                'Provider Name',
                'Service',
                'Api Name',
                'Amount',
                'Total Amount',
                'Commission',
                'Status',
                'Opening',
                'Closing',
                'Path',
                'IP Address',
            ]);
            DB::table($table)

            ->whereBetween('created_at', [$from_date,$to_date])

            ->where('user_id', 'like', '%' . $post->user_id . '%')

            ->where('order_id', 'like', '%' . $post->order_id . '%')

            ->where('number', 'like', '%' . $post->number . '%')

            ->where('service_id', 'like', '%' . $post->service_id . '%')

            ->where('provider_id', 'like', '%' . $post->provider_id . '%')

            ->where('api_id', 'like', '%' . $post->api_id . '%')

            ->where('status', 'like', '%' . $post->status . '%')

            ->where('transaction_type', ['Recharge'])
            ->orderBy('id', 'DESC')
            ->chunk(200, function($rows) use ($handle) {

                foreach ($rows as $list) {
                    $user_dt = DB::table('users')->where('id', $list->user_id)->first();
                    fputcsv($handle, [

                        $user_dt->first_name." ".$user_dt->middle_name." ".$user_dt->last_name,
                        $user_dt->outlet_name,
                        $user_dt->mobile_number,
                        $user_dt->id,
                        $list->request_order_id,
                        $list->order_id,
                        $list->transaction_date,
                        $list->number,
                        DB::table('providers')->where('id', $list->provider_id)->first(['provider_name'])->provider_name,
                        DB::table('services')->where('id', $list->service_id)->first(['service_name'])->service_name,
                        DB::table('apis')->where('id', $list->api_id)->first(['api_name'])->api_name,
                        round($list->amount, 2),
                        round($list->total_amount, 2),
                        round($list->commission, 2),
                        $list->status,
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

        if($post->keyword != ''){

            $user = DB::table('users')
            ->where('deleted_at', '!=' , 1)
            ->where(function ($q) use ($post) {
                $q->where('mobile_number','LIKE','%'.$post->keyword.'%')
                ->orWhere('email_address','LIKE','%'.$post->keyword.'%')
                ->orWhere('outlet_name','LIKE','%'.$post->keyword.'%')
                ->orWhere('first_name','LIKE','%'.$post->keyword.'%')
                ->orWhere('last_name','LIKE','%'.$post->keyword.'%');
            })
            ->get(['id','first_name','middle_name','last_name','outlet_name','mobile_number']);

        }

        return response()->json([

            'users' => $user

        ]);

    }



    public function getProvider(Request $post)

    {

        $rules = array(

            'service_id' => 'required|numeric',

        );



        $validator = \Validator::make($post->all(), array_reverse($rules));

        if ($validator->fails()) {

            foreach ($validator->errors()->messages() as $key => $value) {

                $error = $value[0];

            }

            return response()->json([

                'error_msg' => $error,

                'error' => 1,

            ]);

        }



        $provider = DB::table('providers')->where('service_id', $post->service_id)->where('deleted_at', '!=' , 1)->get(['provider_name','id']);

        return response()->json([

            'data' => $provider,

            'error_msg' => 'Fatch Successfuly.',

            'error' => 0,

        ]);



    }



    public function getApis()

    {

        $apis = DB::table('apis')->where('deleted_at', '!=' , 1)->get(['api_name','id']);

        return response()->json([

            'data' => $apis,

            'error_msg' => 'Fatch Successfuly.',

            'error' => 0,

        ]);

    }



    public function changeOperatorId(Request $post)

    {



        $rules = array(

            'id' => 'required|numeric',

            'operator_id' => 'required',

        );



        $validator = \Validator::make($post->all(), array_reverse($rules));

        if ($validator->fails()) {

            foreach ($validator->errors()->messages() as $key => $value) {

                $error = $value[0];

            }

            return response()->json([

                'error_msg' => $error,

                'error' => 1,

            ]);

        }



        $report = DB::table('reports')->where('id', $post->id)->update(['operator_id' => $post->operator_id]);

        if($report){

            return response()->json([

                'error_msg' => 'Change Successfuly.',

                'error' => 0,

            ]);

        }else{

            return response()->json([

                'error_msg' => 'Somethng went wrong.',

                'error' => 1,

            ]);

        }

    }

    public function getComplaint(Request $post) {



        $rules = array(

            'id' => 'required|numeric',

        );



        $validator = \Validator::make($post->all(), array_reverse($rules));

        if ($validator->fails()) {

            foreach ($validator->errors()->messages() as $key => $value) {

                $error = $value[0];

            }

            return response()->json([

                'error_msg' => $error,

                'error' => 1,

            ]);

        }

        $complaint = DB::table('complaints')->where('id', $post->id)->first();

        if($complaint){

            return response()->json([

                'data' => $complaint,

                'error_msg' => 'Fatch Successfuly.',

                'error' => 0,

            ]);

        }else{

            $data['error'] = 0;

            $data['error_msg'] = "Something went wrong!";

            return response()->json( $data);

        }

        

    }

    public function updateComplaint(Request $post) {

        $rules = array(

            'id' => 'required|numeric',

            'remark' => 'required|string|max:255',

            'status' => 'required|in:Closed,Sloved,Under Review',

        );



        $validator = \Validator::make($post->all(), array_reverse($rules));

        if ($validator->fails()) {

            foreach ($validator->errors()->messages() as $key => $value) {

                $error = $value[0];

            }

            return response()->json([

                'message' => $error,

                'type' => 'error',

            ]);

        }



        $complaint = DB::table('complaints')->where('id', $post->id)->first();

        if($complaint){

            if($post->status == "Sloved"){

                $report = DB::table('reports')->where('id', $complaint->report_id)->first();

                $api_result['status'] = "Refunded";

                $api_result['operator_id'] = "";

                ////$update['api_operator_id'] = "";

                $api_result['remark'] = "Recharge Refunded For Rs. ".$report->total_amount." Number ".$report->number;

                $api_result['api_partner_order_id'] = $report->api_partner_order_id;

                $api_result['order_id'] = $report->order_id;

                DB::table('reports')->where('id', $report->id)->update($api_result);

                $this->refund_row($report->id);

                $this->ReverseCommission($report->id);

                DB::table('reports')->where('id', $complaint->report_id)->update([

                    'complaint_id' => 0

                ]);



                //////Send Sms By Cron Job Start

                //Recharge Refund {NUMBER} Amt {AMOUNT}.00 Oprater {OPERATOR} OPID   Txid {ORDER_ID}.Your current balance is {CURRENT_BALNCE}.00 Thanks! 

                $user_data = DB::table('users')->where('id', $report->user_id)->first(['id','first_name','mobile_number','wallet_balance']);

                $slug = 'recharge_refund';

                $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);

                $content = $sms_tmp->content;

                $content = str_replace('{NAME}', '' . $user_data->first_name . '', $content);

                $content = str_replace('{NUMBER}', '' . $report->number . '', $content);

                $content = str_replace('{AMOUNT}', '' . $report->total_amount . '', $content);

                $content = str_replace('{ORDER_ID}', '' . $report->order_id . '', $content);

                $content = str_replace('{OPERATOR}', '' . Str::upper(DB::table('providers')->where('id', $report->provider_id)->first(['provider_name'])->provider_name) . '', $content);

                $content = str_replace('{CURRENT_BALANCE}', '' . $user_data->wallet_balance . '', $content);

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

                //////Send Sms By Cron Job End

            }else if($post->status == "Closed"){

                DB::table('reports')->where('id', $complaint->report_id)->update([

                    'complaint_id' => 0

                ]);

            }else{

                DB::table('reports')->where('id', $complaint->report_id)->update([

                    'complaint_id' => $complaint->id

                ]);

            }

            try {

                $complaint_u = DB::table('complaints')->where('id', $complaint->id)->update([

                    'decision_by' =>Session::get('user_id'),

                    'decision_remark' => $post->remark,

                    'status' => $post->status,

                    'decision_date' => Carbon::now(),

                    'updated_at' => Carbon::now()

                ]);

                return response()->json([

                    'message' => 'Update Successfuly.',

                    'type' => 'success',

                ]);

            } catch (\Throwable $th) {

                return response()->json(array(

                    'type' => 'error',  

                    'message' => $th

                )); 

            }

        }else{

            return response()->json([

                'message' => 'complaint record not found',

                'type' => 'error',

            ]);

        }

    }





    public function refund_row($report){

        

        $report = DB::table('reports')->where('id', $report)->first();

        $user = DB::table('users')->where('id',$report->user_id)->first();

        //$this->Ptd($user);

        $refund['user_id'] = $user->id;

        $refund['parent__Id'] = $report->id;

        $refund['order_id'] = $report->order_id;

        $refund['number'] = $report->number;

        $refund['amount'] = $report->amount;

        $refund['total_amount'] = $report->total_amount;  

        $refund['admin_commission'] = $report->admin_commission;

        $refund['api_commission'] = $report->api_commission;

        $refund['commission'] = $report->commission;

        $refund['fund_type'] = "Credit";

        $refund['transaction_type'] = "Refund";

        $refund['provider_id'] = $report->provider_id;

        $refund['service_id'] = $report->service_id;

        $refund['api_id'] = $report->api_id;

        $refund['remark'] = "Refund For Rs. ".$report->total_amount." Number ".$report->number;

        $refund['status'] = "Success";

        $refund['path'] = $report->path;

        $refund['ip_address'] = $report->ip_address;

        $refund['opening_balance'] = $user->wallet_balance;

        DB::table('users')->where('id', $report->user_id)->increment('wallet_balance', $report->amount);

        $user = DB::table('users')->where('id',$report->user_id)->first();

        $refund['closing_balance'] = $user->wallet_balance;  

        $refund['transaction_date'] = Carbon::now().":".rand(111,999);

        $refund['created_at'] = Carbon::now();    

        $refund['updated_at'] = Carbon::now();                  

        $report = DB::table('reports')->insertGetId($refund);

        return $report;

     }







     public function updateStatus(Request $post) {

        $rules = array(

            'id' => 'required|numeric',

            'operator_id' => 'max:255',

            'status' => 'required|in:Success,Failed,Refunded,Force Success',

        );



        $validator = \Validator::make($post->all(), array_reverse($rules));

        if ($validator->fails()) {

            foreach ($validator->errors()->messages() as $key => $value) {

                $error = $value[0];

            }

            return response()->json([

                'message' => $error,

                'type' => 'error',

            ]);

        }

        $report = DB::table('reports')->where('id', $post->id)->first();

        if($report){

            if($post->status == "Success" || $post->status == "Under Process"){

                $api_result['status'] = "Success";

                $api_result['operator_id'] = $post->operator_id;

                $api_result['api_operator_id'] = $report->api_operator_id;;

                $api_result['remark'] = "Recharge Successful For Rs. ".$report->total_amount." Number ".$report->number;

                $api_result['api_partner_order_id'] = $report->api_partner_order_id;

                $api_result['order_id'] = $report->order_id;

                DB::table('reports')->where('id', $report->id)->update($api_result);

                $this->SetCommission($report->id);

            }else if($post->status == "Failed"){

                $api_result['status'] = "Failed";

                $api_result['operator_id'] = "";

                ////$update['api_operator_id'] = "";

                $api_result['remark'] = "Recharge Failed For Rs. ".$report->total_amount." Number ".$report->number;

                $api_result['api_partner_order_id'] = $report->api_partner_order_id;

                $api_result['order_id'] = $report->order_id;

                DB::table('reports')->where('id', $report->id)->update($api_result);

                $this->refund_row($report->id);

            }else if($post->status == "Refunded"){

                $api_result['status'] = "Refunded";

                $api_result['operator_id'] = "";

                ////$update['api_operator_id'] = "";

                $api_result['remark'] = "Recharge Refunded For Rs. ".$report->total_amount." Number ".$report->number;

                $api_result['api_partner_order_id'] = $report->api_partner_order_id;

                $api_result['order_id'] = $report->order_id;

                DB::table('reports')->where('id', $report->id)->update($api_result);

                $this->refund_row($report->id);

                $this->ReverseCommission($report->id);



                //////Send Sms By Cron Job Start

                //Recharge Refund {NUMBER} Amt {AMOUNT}.00 Oprater {OPERATOR} OPID   Txid {ORDER_ID}.Your current balance is {CURRENT_BALNCE}.00 Thanks! 

                $user_data = DB::table('users')->where('id', $report->user_id)->first(['id','first_name','mobile_number','wallet_balance']);

                $slug = 'recharge_refund';

                $sms_tmp = DB::table('sms_templates')->where('slug', $slug)->first(['template_id','content','status']);

                $content = $sms_tmp->content;

                $content = str_replace('{NAME}', '' . $user_data->first_name . '', $content);

                $content = str_replace('{NUMBER}', '' . $report->number . '', $content);

                $content = str_replace('{AMOUNT}', '' . $report->total_amount . '', $content);

                $content = str_replace('{ORDER_ID}', '' . $report->order_id . '', $content);

                $content = str_replace('{OPERATOR}', '' . Str::upper(DB::table('providers')->where('id', $report->provider_id)->first(['provider_name'])->provider_name) . '', $content);

                $content = str_replace('{CURRENT_BALANCE}', '' . $user_data->wallet_balance . '', $content);

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

                //////Send Sms By Cron Job End

                

            }else if($post->status == "Force Success"){

                //$user = DB::table('users')->where('id',$report->user_id)->first();

                //return $user;

                return response()->json([

                    'message' => 'Force Success Not Work',

                    'type' => 'error',

                ]);

            }else{

                return response()->json([

                    'message' => 'please pass valid status',

                    'type' => 'error',

                ]);

            }

            return response()->json([

                'message' => 'Update Successfuly.',

                'type' => 'success',

            ]);

            //return $report;

        }else{

            return response()->json([

                'message' => 'reports record not found',

                'type' => 'error',

            ]);

        }

    }





    public function ReverseCommission($report)
    {
        $report = DB::table('reports')->where('id', $report)->first();

        $reports = DB::table('reports')->where('order_id', $report->order_id)->where('total_amount', $report->total_amount)->where('transaction_type', 'Commission')->get();
        if(!$reports){
            return 0;
        }
        //echo "<pre>";print_r($reports);die;
        foreach($reports as $report){
            $user = DB::table('users')->where('id', $report->user_id)->first();
            $data['user_id'] = $report->user_id;

             $data['parent__Id'] = $report->id;

             $data['order_id'] = $report->order_id;

             $data['number'] = $report->number;

             $data['amount'] = $report->amount;

             $data['total_amount'] = $report->total_amount;  

             $data['commission'] = $report->commission;

             $data['fund_type'] = "Debit";

             $data['transaction_type'] = "Reverse Commission";

             $data['provider_id'] = $report->provider_id;

             $data['service_id'] = $report->service_id;

             $data['api_id'] = $report->api_id;

             $data['remark'] = "Reverse Commission For Rs. ".$report->total_amount." Number ".$report->number." Recharge Refunded";

             $data['status'] = "Success";

             $data['path'] = $report->path;

             $data['ip_address'] = $report->ip_address;

             $data['opening_balance'] = $user->wallet_balance;

             $data['transaction_date'] = Carbon::now().":".rand(111,999);

             $data['created_at'] = Carbon::now();

             $data['updated_at'] = Carbon::now();

             DB::table('users')->where('id', $user->id)->decrement('wallet_balance', $report->commission);

             $user = DB::table('users')->where('id', $user->id)->first();

             $data['closing_balance'] = $user->wallet_balance;

             $update = DB::table('reports')->insertGetId($data);
        }
        return 0;
    }





    public static function SetCommission($report)

     {

        $report_id_com = $report;

         ///1 Level Commission

         $report = DB::table('reports')->where('id', $report)->first();

         $user = DB::table('users')->where('id', $report->user_id)->first();
         $scheme_id = $user->scheme_id;
 

         if($user->parent_id !=0 && $user->parent_id !=1){

             $parent = DB::table('users')->where('id', $user->parent_id)->first();

            //  $commission_user = \helpers::getCommission($report->total_amount, $user->scheme_id, $report->provider_id,$user->role_id);

            //  $commission_parent = \helpers::getCommission($report->total_amount, $parent->scheme_id, $report->provider_id,$parent->role_id);

            //  $commission =  $commission_parent - $commission_user;

            $commission_parent = Common::getCommission($report->total_amount,$scheme_id, $report->provider_id,$parent->role_id);
            $commission =  $commission_parent;

             $data['user_id'] = $parent->id;

             $data['parent__Id'] = $report->id;

             $data['order_id'] = $report->order_id;

             $data['number'] = $report->number;

             $data['amount'] = $commission;

             $data['total_amount'] = $report->total_amount;  

             //$data['admin_commission'] = $report->admin_commission;

             //$data['api_commission'] = $report->api_commission;

             $data['commission'] = $commission;

             $data['fund_type'] = "Credit";

             $data['transaction_type'] = "Commission";

             $data['provider_id'] = $report->provider_id;

             $data['service_id'] = $report->service_id;

             $data['api_id'] = $report->api_id;

             $data['remark'] = "Commission For Rs. ".$report->total_amount." Number ".$report->number;

             $data['status'] = "Success";

             $data['path'] = $report->path;

             $data['ip_address'] = $report->ip_address;

             $data['opening_balance'] = $parent->wallet_balance;

             $data['transaction_date'] = Carbon::now().":".rand(111,999);

             $data['created_at'] = Carbon::now();

             $data['updated_at'] = Carbon::now();

             DB::table('users')->where('id', $parent->id)->increment('wallet_balance', $commission);

             $parent = DB::table('users')->where('id', $parent->id)->first();

             $data['closing_balance'] = $parent->wallet_balance;                    

             $report = DB::table('reports')->insertGetId($data);

             ////

             DB::table('reports')->where('id', $report_id_com)->update(['dt_commission' => $commission]);

             ///

             ///2 Level Commission

             $report = DB::table('reports')->where('id', $report)->first();

             $user = DB::table('users')->where('id', $report->user_id)->first();

             if($user->parent_id !=0 && $user->parent_id !=1){

                 $parent = DB::table('users')->where('id', $user->parent_id)->first();

                //  $commission_user = \helpers::getCommission($report->total_amount, $user->scheme_id, $report->provider_id,$user->role_id);

                //  $commission_parent = \helpers::getCommission($report->total_amount, $parent->scheme_id, $report->provider_id,$parent->role_id);

                //  $commission =  $commission_parent - $commission_user;

                $commission_parent = Common::getCommission($report->total_amount,$scheme_id, $report->provider_id,$parent->role_id);
                $commission =  $commission_parent;

                 $data['user_id'] = $parent->id;

                 $data['parent__Id'] = $report->id;

                 $data['order_id'] = $report->order_id;

                 $data['number'] = $report->number;

                 $data['amount'] = $commission;

                 $data['total_amount'] = $report->total_amount;  

                 //$data['admin_commission'] = $report->admin_commission;

                 //$data['api_commission'] = $report->api_commission;

                 $data['commission'] = $commission;

                 $data['fund_type'] = "Credit";

                 $data['transaction_type'] = "Commission";

                 $data['provider_id'] = $report->provider_id;

                 $data['service_id'] = $report->service_id;

                 $data['api_id'] = $report->api_id;

                 $data['remark'] = "Commission For Rs. ".$report->total_amount." Number ".$report->number;

                 $data['status'] = "Success";

                 $data['path'] = $report->path;

                 $data['ip_address'] = $report->ip_address;

                 $data['opening_balance'] = $parent->wallet_balance;

                 $data['transaction_date'] = Carbon::now().":".rand(111,999);

                 $data['created_at'] = Carbon::now();

                 $data['updated_at'] = Carbon::now();

                 DB::table('users')->where('id', $parent->id)->increment('wallet_balance', $commission);

                 $parent = DB::table('users')->where('id', $parent->id)->first();

                 $data['closing_balance'] = $parent->wallet_balance;                    

                 $report = DB::table('reports')->insertGetId($data);

                 ////

                DB::table('reports')->where('id', $report_id_com)->update(['md_commission' => $commission]);

                ///

                 ///3 Level Commission

                 $report = DB::table('reports')->where('id', $report)->first();

                 $user = DB::table('users')->where('id', $report->user_id)->first();

                 if($user->parent_id !=0 && $user->parent_id !=1){

                     $parent = DB::table('users')->where('id', $user->parent_id)->first();

                    //  $commission_user = \helpers::getCommission($report->total_amount, $user->scheme_id, $report->provider_id,$user->role_id);

                    //  $commission_parent = \helpers::getCommission($report->total_amount, $parent->scheme_id, $report->provider_id,$parent->role_id);

                    //  $commission =  $commission_parent - $commission_user;

                    $commission_parent = Common::getCommission($report->total_amount,$scheme_id, $report->provider_id,$parent->role_id);
                    $commission =  $commission_parent;

                     $data['user_id'] = $parent->id;

                     $data['parent__Id'] = $report->id;

                     $data['order_id'] = $report->order_id;

                     $data['number'] = $report->number;

                     $data['amount'] = $commission;

                     $data['total_amount'] = $report->total_amount;  

                     //$data['admin_commission'] = $report->admin_commission;

                     //$data['api_commission'] = $report->api_commission;

                     $data['commission'] = $commission;

                     $data['fund_type'] = "Credit";

                     $data['transaction_type'] = "Commission";

                     $data['provider_id'] = $report->provider_id;

                     $data['service_id'] = $report->service_id;

                     $data['api_id'] = $report->api_id;

                     $data['remark'] = "Commission For Rs. ".$report->total_amount." Number ".$report->number;

                     $data['status'] = "Success";

                     $data['path'] = $report->path;

                     $data['ip_address'] = $report->ip_address;

                     $data['opening_balance'] = $parent->wallet_balance;

                     $data['transaction_date'] = Carbon::now().":".rand(111,999);

                     $data['created_at'] = Carbon::now();

                     $data['updated_at'] = Carbon::now();

                     DB::table('users')->where('id', $parent->id)->increment('wallet_balance', $commission);

                     $parent = DB::table('users')->where('id', $parent->id)->first();

                     $data['closing_balance'] = $parent->wallet_balance;                    

                     $report = DB::table('reports')->insertGetId($data);

                     ////

                    DB::table('reports')->where('id', $report_id_com)->update(['wt_commission' => $commission]);

                    ///

                     ///4 Level Commission

                     $report = DB::table('reports')->where('id', $report)->first();

                     $user = DB::table('users')->where('id', $report->user_id)->first();     

                 }  

             }

         }

     }





     public function getCommission($amount, $scheme, $provider_id, $role_id)

    {

        $myscheme = DB::table('schemes')->where('id', $scheme)->first();

        

        if($myscheme && $myscheme->status == "1"){

            $comdata = DB::table('scheme_commissions')->where('provider_id', $provider_id)->where('scheme_id', $scheme)->first();

            if ($comdata) {

                if($role_id== 6){

                    if ($comdata->rt_amount_type == "Commission Percent") {

                        $commission = $amount * $comdata->rt_amount_value / 100;

                    }else{

                        $commission = $comdata->rt_amount_value;

                    }

                }else if($role_id== 5){

                    if ($comdata->dt_amount_type == "Commission Percent") {

                        $commission = $amount * $comdata->dt_amount_value / 100;

                    }else{

                        $commission = $comdata->dt_amount_value;

                    }

                }else if($role_id== 4){

                    if ($comdata->md_amount_type == "Commission Percent") {

                        $commission = $amount * $comdata->md_amount_value / 100;

                    }else{

                        $commission = $comdata->md_amount_value;

                    }

                }else if($role_id== 2){

                    if ($comdata->wt_amount_type == "Commission Percent") {

                        $commission = $amount * $comdata->wt_amount_value / 100;

                    }else{

                        $commission = $comdata->wt_amount_value;

                    }

                }else{

                    $commission = 0;

                }

                if($commission == null){

                    $commission = 0;

                }

            }else{

                $commission = 0;

            }

        }else{

            $commission = 0;

        }

        

        return $commission;

    }





    public function checkApiLog(Request $post) {

        $rules = array(

            'id' => 'string|max:255',

        );



        $validator = \Validator::make($post->all(), array_reverse($rules));

        if ($validator->fails()) {

            foreach ($validator->errors()->messages() as $key => $value) {

                $error = $value[0];

            }

            return response()->json([

                'message' => $error,

                'type' => 'error',

            ]);

        }

        $api_logs = DB::table('apilogs')->where('txnid', $post->id)->get();

        if($api_logs){

            return response()->json([

                'data' => $api_logs,

                'message' => 'Fatch Successfuly.',

                'type' => 'success',

            ]);

        }else{

            return response()->json([

                'message' => 'api logs record not found.',

                'type' => 'error',

            ]);

        }

    }





}

