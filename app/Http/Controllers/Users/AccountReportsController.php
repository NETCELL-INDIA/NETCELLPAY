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
        $filterQuery = function () use ($table, $from_date, $to_date, $post) {
            return DB::table($table)
                ->where('user_id', Session::get('user_id'))
                ->whereBetween('created_at', [$from_date,$to_date])
                ->where('order_id', 'like', '%' . $post->order_id . '%')
                ->where('transaction_type', 'like', '%' . $post->tr_type . '%')
                ->where('fund_type', 'like', '%' . $post->fund_type . '%');
        };
        $total_row_count = $filterQuery()->count();
        $totals = $filterQuery()->selectRaw("
                COALESCE(SUM(CASE WHEN fund_type = 'Credit' THEN amount ELSE 0 END), 0) as total_credit,
                COALESCE(SUM(CASE WHEN fund_type = 'Debit' THEN amount ELSE 0 END), 0) as total_debit
            ")->first();
        $total_credit = (float) ($totals->total_credit ?? 0);
        $total_debit = (float) ($totals->total_debit ?? 0);
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
        
        
        $list = $filterQuery()
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
        $output .= '<div class="row g-2 mb-3">
            <div class="col-md-4"><div class="border rounded p-2 bg-success-subtle"><small class="text-muted">Total Credit</small><div class="fw-bold text-success">₹ '.number_format($total_credit, 2).'</div></div></div>
            <div class="col-md-4"><div class="border rounded p-2 bg-danger-subtle"><small class="text-muted">Total Debit</small><div class="fw-bold text-danger">₹ '.number_format($total_debit, 2).'</div></div></div>
            <div class="col-md-4"><div class="border rounded p-2"><small class="text-muted">Net (Credit − Debit)</small><div class="fw-bold">₹ '.number_format($total_credit - $total_debit, 2).'</div></div></div>
        </div>';
        $output .= '<table class="table table-bordered table-nowrap" id="pagination_table"><thead>
              <tr>
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
                $amt = (float) $list->amount;
                $creditCell = ($list->fund_type == 'Credit')
                    ? '<span class="text-success fw-bold">₹ '.number_format($amt, 2).'</span>'
                    : '—';
                $debitCell = ($list->fund_type == 'Debit')
                    ? '<span class="text-danger fw-bold">₹ '.number_format($amt, 2).'</span>'
                    : '—';
				$output .= '<tr>
                <td>' . e($list->transaction_date) . '</td>
                <td>' . e($list->order_id) . '</td>
                <td>
                    <span class="badge badge-gradient-info">' . e($list->transaction_type) . '</span>
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

}
