<?php



namespace App\Http\Controllers\Admin;
use Symfony\Component\HttpFoundation\StreamedResponse;


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

        return view('admin.user-reports.account-report');

    }



    public function fetchAll(Request $post)

    {

        $rules = array(

            'page' => 'required|numeric',

            'limit' => 'required|numeric',

            'tr_type' => 'required|in:Admin Fund,All,Transfer Money,Receive Money,Upi Add Money,Self Money,Reverse Money,Money Reverse,Commission,Recharge,Reverse Commission,Refund,Money Transfer',

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

        $filterQuery = function () use ($table, $from_date, $to_date, $post) {
            return $this->accountReportQuery($table, $from_date, $to_date, $post);
        };

        $total_row_count = $filterQuery()->count();

        $totals = $filterQuery()->selectRaw("
                COALESCE(SUM(CASE WHEN fund_type = 'Credit' THEN amount ELSE 0 END), 0) as total_credit,
                COALESCE(SUM(CASE WHEN fund_type = 'Debit' THEN amount ELSE 0 END), 0) as total_debit,
                COALESCE(SUM(CASE WHEN transaction_type IN ('Self Money', 'Upi Add Money') THEN amount ELSE 0 END), 0) as total_add
            ")->first();
        $total_credit = (float) ($totals->total_credit ?? 0);
        $total_debit = (float) ($totals->total_debit ?? 0);
        $total_add = (float) ($totals->total_add ?? 0);

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

        

        $list = $filterQuery()
            ->orderBy('id', 'DESC')
            ->offset($start)
            ->limit($limit)
            ->get();

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

            $output .= '<div class="row g-2 mb-3">
                <div class="col-md-3"><div class="border rounded p-2 bg-success-subtle"><small class="text-muted">Total Credit</small><div class="fw-bold text-success">₹ '.number_format($total_credit, 2).'</div></div></div>
                <div class="col-md-3"><div class="border rounded p-2 bg-danger-subtle"><small class="text-muted">Total Debit</small><div class="fw-bold text-danger">₹ '.number_format($total_debit, 2).'</div></div></div>
                <div class="col-md-3"><div class="border rounded p-2 bg-primary-subtle"><small class="text-muted">Total Add</small><div class="fw-bold text-primary">₹ '.number_format($total_add, 2).'</div></div></div>
                <div class="col-md-3"><div class="border rounded p-2"><small class="text-muted">Net (Credit − Debit)</small><div class="fw-bold">₹ '.number_format($total_credit - $total_debit, 2).'</div></div></div>
            </div>';

            $output .= '<table class="table table-bordered table-nowrap" id="pagination_table"><thead>
              <tr>
                <th>User</th>
                <th>Date &amp; Time</th>
                <th>Order Id</th>
                <th>Type</th>
                <th>Remark</th>
                <th class="text-end">Credit</th>
                <th class="text-end">Debit</th>
                <th class="text-end">Opening</th>
                <th class="text-end">Closing</th>
              </tr>
            </thead>
            <tbody>';

            $i=$start + 1;

			foreach ($list as $list) {

                $user_dt = DB::table('users')->where('id', $list->user_id)->first(['first_name','middle_name','last_name','outlet_name','mobile_number']);

                $user_dt_first_name = $user_dt->first_name ?? '-';
                $user_dt_middle_name = $user_dt->middle_name ?? '';
                $user_dt_last_name = $user_dt->last_name ?? '';
                $user_dt_outlet_name = $user_dt->outlet_name ?? '-';
                $user_dt_mobile_number = $user_dt->mobile_number ?? '-';

                $amt = (float) $list->amount;
                $creditCell = ($list->fund_type == 'Credit')
                    ? '<span class="text-success fw-bold">₹ '.number_format($amt, 2).'</span>'
                    : '—';
                $debitCell = ($list->fund_type == 'Debit')
                    ? '<span class="text-danger fw-bold">₹ '.number_format($amt, 2).'</span>'
                    : '—';

				$output .= '<tr>
                <td>
                    ' . e(trim($user_dt_first_name.' '.$user_dt_middle_name.' '.$user_dt_last_name)) . ' <br>
                    <small>' . e($user_dt_outlet_name) . ' · ' . e($user_dt_mobile_number) . '</small>
                </td>
                <td>' . e($list->transaction_date) . '</td>
                <td>' . e($list->order_id) . '</td>
                <td>
                    <span class="badge ' . $this->adminTxnKindBadge($list->transaction_type, $list->fund_type) . '">' . e($this->adminTxnKindLabel($list->transaction_type, $list->fund_type)) . '</span>
                    <div><small>' . e($list->transaction_type) . '</small></div>
                </td>
                <td>' . e($list->remark) . '</td>
                <td class="text-end">' . $creditCell . '</td>
                <td class="text-end">' . $debitCell . '</td>
                <td class="text-end"> ₹ ' . number_format((float) $list->opening_balance, 2) . '</td>
                <td class="text-end"> ₹ ' . number_format((float) $list->closing_balance, 2) . '</td>
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
                'Remark',
                'Credit',
                'Debit',
                'Opening',
                'Closing',
                'Path',
                'IP Address',
            ]);
            $this->accountReportQuery($table, $from_date, $to_date, $post)
            ->orderBy('id', 'ASC')
            ->chunk(200, function($rows) use ($handle) {

                foreach ($rows as $list) {
                    $user_dt = DB::table('users')->where('id', $list->user_id)->first();
                    $isCredit = ($list->fund_type == 'Credit');
                    fputcsv($handle, [
                        trim(($user_dt->first_name ?? '') . ' ' . ($user_dt->middle_name ?? '') . ' ' . ($user_dt->last_name ?? '')),
                        $user_dt->outlet_name ?? '',
                        $user_dt->mobile_number ?? '',
                        $user_dt->id ?? $list->user_id,
                        $list->order_id,
                        $list->transaction_date,
                        $list->transaction_type,
                        $list->remark,
                        $isCredit ? round((float) $list->amount, 2) : '',
                        $isCredit ? '' : round((float) $list->amount, 2),
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
                $q->where('mobile_number','LIKE','%'.$keyword.'%')
                ->orWhere('email_address','LIKE','%'.$keyword.'%')
                ->orWhere('outlet_name','LIKE','%'.$keyword.'%')
                ->orWhere('first_name','LIKE','%'.$keyword.'%')
                ->orWhere('last_name','LIKE','%'.$keyword.'%');
            })
            ->limit(25)
            ->get(['id','first_name','middle_name','last_name','outlet_name','mobile_number']);

        }

        return response()->json([

            'users' => $user

        ]);

    }

    private function adminFundTypes(): array
    {
        return [
            'Transfer Money',
            'Receive Money',
            'Reverse Money',
            'Money Reverse',
            'Self Money',
            'Upi Add Money',
        ];
    }

    private function accountReportQuery(string $table, string $from_date, string $to_date, $post)
    {
        $q = DB::table($table)->whereBetween('created_at', [$from_date, $to_date]);

        if ($post->user_id !== '' && $post->user_id !== null) {
            $q->where('user_id', $post->user_id);
        }
        if (!empty($post->order_id)) {
            $q->where('order_id', 'like', '%' . $post->order_id . '%');
        }
        if (!empty($post->fund_type)) {
            $q->where('fund_type', $post->fund_type);
        }

        $tr = (string) $post->tr_type;
        if ($tr === 'Admin Fund') {
            $q->whereIn('transaction_type', $this->adminFundTypes());
        } elseif ($tr !== '') {
            $q->where('transaction_type', $tr);
        }

        return $q;
    }

    private function adminTxnKindLabel($transactionType, $fundType): string
    {
        if (in_array($transactionType, ['Self Money', 'Upi Add Money'], true)) {
            return 'Add';
        }
        if ($fundType === 'Credit') {
            return 'Credit';
        }
        if ($fundType === 'Debit') {
            return 'Debit';
        }
        return (string) $transactionType;
    }

    private function adminTxnKindBadge($transactionType, $fundType): string
    {
        $kind = $this->adminTxnKindLabel($transactionType, $fundType);
        if ($kind === 'Add') {
            return 'badge bg-primary';
        }
        if ($kind === 'Credit') {
            return 'badge bg-success';
        }
        if ($kind === 'Debit') {
            return 'badge bg-danger';
        }
        return 'badge badge-gradient-info';
    }

}

