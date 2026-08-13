<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Redirect;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Session;
class AdminReportsController extends Controller
{
    public function liveRechargeReports(Request $post)
    {
        $apis = DB::table('apis')->orderBy('api_name')->get(['id', 'api_name']);
        $services = DB::table('services')->orderBy('service_name')->get(['id', 'service_name']);
        $providers = DB::table('providers')->orderBy('provider_name')->get(['id', 'provider_name', 'service_id']);
        $circles = DB::table('states')->orderBy('state_name')->get(['id', 'state_name']);

        return view('admin.admin-reports.live-recharge-report', compact('apis', 'services', 'providers', 'circles'));
    }

    private function liveRechargeBaseQuery(Request $post)
    {
        $q = DB::table('reports as r')
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
            if (Schema::hasColumn('reports', 'path')) {
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
        if ($post->keep_number) {
            $q->where('r.number', $post->keep_number);
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

        return $q;
    }

    public function liveRechargeReportsList(Request $post)
    {
        $limit = (int) ($post->show ?: 10);
        if (!in_array($limit, [10, 25, 50, 100], true)) {
            $limit = 10;
        }
        $page = max(1, (int) ($post->page ?: 1));
        $offset = ($page - 1) * $limit;

        $base = $this->liveRechargeBaseQuery($post);
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

        $hasPath = Schema::hasColumn('reports', 'path');
        $rows = '';
        if ($reports->count() > 0) {
            foreach ($reports as $list) {
                $status = $list->status ?: '-';
                $userDetails = trim(($list->outlet_name ?: $list->first_name ?: 'User') . ' / ' . ($list->mobile_number ?: '-') . ' / ID:' . ($list->user_id ?: '-'));
                $dt = $list->transaction_date ?: $list->created_at;
                $mode = $hasPath ? ($list->path ?: '-') : ($list->fund_type ?: 'WEB');
                $idsLine = '<span>' . e($list->operator_id ?: '-') . '</span> / '
                    . '<span style="color:#e6a700;">' . e($list->operator_id ?: '-') . '</span> / '
                    . '<span style="color:#0dcaf0;">' . e($list->request_order_id ?: '-') . '</span>';

                $rows .= '<tr data-number="' . e($list->number ?: '') . '">
                    <td><strong>' . e($list->order_id ?: ('R' . $list->id)) . '</strong><br><small class="text-muted">#' . e($list->id) . '</small></td>
                    <td>' . e($dt) . '</td>
                    <td>' . e($userDetails) . '</td>
                    <td>' . e($list->provider_name ?: '-') . '</td>
                    <td>' . e($list->circle_name ?: '-') . '</td>
                    <td class="live-number" style="cursor:pointer;">' . e($list->number ?: '-') . '</td>
                    <td>₹' . number_format((float) $list->amount, 2) . '</td>
                    <td>' . report_status_html($status, $list->id) . '</td>
                    <td>' . e($list->api_name ?: '-') . '</td>
                    <td><small>' . $idsLine . '</small></td>
                    <td>' . e(strtoupper((string) $mode)) . '</td>
                </tr>';
            }
        } else {
            $rows = '<tr><td colspan="11" class="text-center text-muted py-4">No data available in table</td></tr>';
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
                'last_page' => max(1, (int) ceil($total / $limit)),
            ],
        ]);
    }

    public function liveRechargeReportsDownload(Request $post)
    {
        $reports = $this->liveRechargeBaseQuery($post)
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

        $filename = 'live-recharge-report-' . date('Ymd-His') . '.csv';
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


    public function userSaleReports(Request $post)
    {
        return view('admin.admin-reports.user-sale-report');
    }


    public function userSaleReportsList(Request $post) {
            $start_date2 = Carbon::now()->format('Y-m-d').' 00:00:00';
            $end_date2 = Carbon::now()->format('Y-m-d').' 23:59:59';
            $where = "";
            if($post->from_date){
                $start_date2 = $post->from_date.' 00:00:00';
                $end_date2 = $post->to_date.' 23:59:59';
                if($post->user_id!=0){
                    $where = " AND u.id = '".$post->user_id."'"; 
                }
            }
            // echo "<pre>";print_r($start_date2);//die;
            // echo "<pre>";print_r($end_date2);die;
            $query2 =  "SELECT 
            u.outlet_name, 
            u.mobile_number,
            r.user_id as id,    
            COUNT(IF(r.status = 'Pending', 1, NULL)) 'PendingHit',
            COUNT(IF(r.status = 'Failed', 1, NULL)) 'FailedHit',
            COUNT(IF(r.status = 'Success', 1, NULL)) 'SuccessHit',
            COUNT(IF(r.status = 'Refunded', 1, NULL)) 'RefundedHit',
            COUNT(r.id) 'TotalHit' ,
            SUM(CASE WHEN r.status = 'Pending' THEN total_amount ELSE 0 END) PendingAmt,
            SUM(CASE WHEN r.status = 'Failed' THEN total_amount ELSE 0 END) FailedAmt,
            SUM(CASE WHEN r.status = 'Success' THEN total_amount ELSE 0 END) SuccessAmt,
            SUM(CASE WHEN r.status = 'Refunded' THEN total_amount ELSE 0 END) RefundedAmt,
            SUM(r.total_amount) 'TotalAmt',
            SUM(CASE WHEN r.status = 'Success' THEN commission ELSE 0 END) Comm
            FROM `reports` as r JOIN users as u ON u.id=r.user_id  
            WHERE r.created_at between '$start_date2' AND '$end_date2' AND transaction_type IN ('Recharge', 'Bill Pay') $where
            group by r.user_id,u.outlet_name,u.mobile_number";
           //echo "<pre>";print_r($query2);die;
            $reports = DB::select($query2);
            $output = '';
            if (count($reports) > 0) {
                $output .= '<table id="scroll-vertical" class="table table-bordered dt-responsive nowrap align-middle mdl-data-table" style="width:100%;text-transform: uppercase;">
                <thead>
                <tr>
                    <th>User Details</th>
                    <th>Qty/Total Amount</th>
                    <th>Qty/Success Amount</th>
                    <th>Qty/Failed Amount</th>
                    <th>Qty/Refunded Amount</th>
                    <th>Qty/Pending Amount</th>
                    <th>Commission</th>
                </tr>
                </thead>
                <tbody>';
                $i=1;
                $totalhit = 0;
                $totalamt = 0;
                $successhit = 0;
                $successamt = 0;
                $failedhit = 0;
                $failedamt = 0;
                $refundedhit = 0;
                $frefundedamt = 0;
                $pendingamt = 0;
                $pendinghit = 0;
                $totalcomm = 0;
                foreach ($reports as $list) {
                    $output .= '<tr>
                        <td class="text-center font-weight-bold">' . $list->outlet_name . ' - ' . $list->mobile_number . ' (' . $list->id . ')</td>
                        <td class="text-center font-weight-bold">' . $list->TotalHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->TotalAmt, 2) . '</td>
                        <td class="text-center font-weight-bold">' . $list->SuccessHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->SuccessAmt, 2). '</td>
                        <td class="text-center font-weight-bold">' . $list->FailedHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->FailedAmt, 2) . '</td>
                        <td class="text-center font-weight-bold">' . $list->RefundedHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->RefundedAmt, 2) . '</td>
                        <td class="text-center font-weight-bold">' . $list->PendingHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->PendingAmt, 2) . '</td>
                        <td class="text-center font-weight-bold"><i class="las la-rupee-sign"></i> ' . number_format((float) $list->Comm, 2) . '</td>
                    </tr>';
                    $totalhit +=$list->TotalHit;
                    $totalamt +=$list->TotalAmt;
                    $successhit +=$list->SuccessHit;
                    $successamt +=$list->SuccessAmt;
                    $failedhit +=$list->FailedHit;
                    $failedamt +=$list->FailedAmt;
                    $refundedhit +=$list->RefundedHit;
                    $frefundedamt +=$list->RefundedAmt;
                    $pendingamt +=$list->PendingAmt;
                    $pendinghit +=$list->PendingHit;
                    $totalcomm +=$list->Comm;
                    $i++;
                }
                $output .= '</tbody><tfoot>
                    <tr>
                        <td class="text-center font-weight-bold">Total</td>
                        <td class="text-center font-weight-bold"> ' . $totalhit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float)$totalamt, 2) . '</td>
                        <td class="text-center font-weight-bold"> ' . $successhit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float)$successamt, 2) . '</td>
                        <td class="text-center font-weight-bold"> ' . $failedhit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float)$failedamt, 2) . '</td>
                        <td class="text-center font-weight-bold"> ' . $refundedhit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float)$frefundedamt, 2) . '</td>
                        <td class="text-center font-weight-bold"> ' . $pendinghit . ' /<i class="las la-rupee-sign"></i>  ' . number_format((float)$pendingamt, 2) . '</td>
                        <td class="text-center font-weight-bold"><i class="las la-rupee-sign"></i> ' . number_format((float)$totalcomm, 2) . '</td>
                    </tr>
                </tfoot>';
                $output .= '</table>';
                return $output;
                //echo '<h4 class="text-center text-secondary my-3">record found</h4>';
            }else{
                echo '<h4 class="text-center text-secondary my-3">No record found</h4>';
            }
        //return $reports;
    }



    public function mdAndDtSaleReports(Request $post)
    {
        return view('admin.admin-reports.md-dt-sale-report');
    }


    public function mdAndDtSaleReportsList(Request $post) {
            $start_date2 = Carbon::now()->format('Y-m-d').' 00:00:00';
            $end_date2 = Carbon::now()->format('Y-m-d').' 23:59:59';
            $where = "";
            if($post->from_date){
                $start_date2 = $post->from_date.' 00:00:00';
                $end_date2 = $post->to_date.' 23:59:59';
                if($post->user_id!=0){
                    $where = " AND u.id = '".$post->user_id."'"; 
                }
            }
            // echo "<pre>";print_r($start_date2);//die;
            // echo "<pre>";print_r($end_date2);die;
            $query2 =  "SELECT 
            u.outlet_name, 
            u.mobile_number,
            r.user_id as id,    
            COUNT(IF(r.status = 'Pending', 1, NULL)) 'PendingHit',
            COUNT(IF(r.status = 'Failed', 1, NULL)) 'FailedHit',
            COUNT(IF(r.status = 'Success', 1, NULL)) 'SuccessHit',
            COUNT(IF(r.status = 'Refunded', 1, NULL)) 'RefundedHit',
            COUNT(r.id) 'TotalHit' ,
            SUM(CASE WHEN r.status = 'Pending' THEN total_amount ELSE 0 END) PendingAmt,
            SUM(CASE WHEN r.status = 'Failed' THEN total_amount ELSE 0 END) FailedAmt,
            SUM(CASE WHEN r.status = 'Success' THEN total_amount ELSE 0 END) SuccessAmt,
            SUM(CASE WHEN r.status = 'Refunded' THEN total_amount ELSE 0 END) RefundedAmt,
            SUM(r.total_amount) 'TotalAmt',
            SUM(CASE WHEN r.status = 'Success' THEN commission ELSE 0 END) Comm
            FROM `reports` as r JOIN users as u ON u.id=r.user_id  
            WHERE r.created_at between '$start_date2' AND '$end_date2' AND transaction_type IN ('Commission') $where
            group by r.user_id,u.outlet_name,u.mobile_number";
           //echo "<pre>";print_r($query2);die;
            $reports = DB::select($query2);
            $output = '';
            if (count($reports) > 0) {
                $output .= '<table id="scroll-vertical" class="table table-bordered dt-responsive nowrap align-middle mdl-data-table" style="width:100%;text-transform: uppercase;">
                <thead>
                <tr>
                    <th>User Details</th>
                    <th>Qty/Total Amount</th>
                    <th>Qty/Success Amount</th>
                    <th>Qty/Failed Amount</th>
                    <th>Qty/Refunded Amount</th>
                    <th>Qty/Pending Amount</th>
                    <th>Commission</th>
                </tr>
                </thead>
                <tbody>';
                $i=1;
                $totalhit = 0;
                $totalamt = 0;
                $successhit = 0;
                $successamt = 0;
                $failedhit = 0;
                $failedamt = 0;
                $refundedhit = 0;
                $frefundedamt = 0;
                $pendingamt = 0;
                $pendinghit = 0;
                $totalcomm = 0;
                foreach ($reports as $list) {
                    $output .= '<tr>
                        <td class="text-center font-weight-bold">' . $list->outlet_name . ' - ' . $list->mobile_number . ' (' . $list->id . ')</td>
                        <td class="text-center font-weight-bold">' . $list->TotalHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->TotalAmt, 2) . '</td>
                        <td class="text-center font-weight-bold">' . $list->SuccessHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->SuccessAmt, 2). '</td>
                        <td class="text-center font-weight-bold">' . $list->FailedHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->FailedAmt, 2) . '</td>
                        <td class="text-center font-weight-bold">' . $list->RefundedHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->RefundedAmt, 2) . '</td>
                        <td class="text-center font-weight-bold">' . $list->PendingHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->PendingAmt, 2) . '</td>
                        <td class="text-center font-weight-bold"><i class="las la-rupee-sign"></i> ' . number_format((float) $list->Comm, 2) . '</td>
                    </tr>';
                    $totalhit +=$list->TotalHit;
                    $totalamt +=$list->TotalAmt;
                    $successhit +=$list->SuccessHit;
                    $successamt +=$list->SuccessAmt;
                    $failedhit +=$list->FailedHit;
                    $failedamt +=$list->FailedAmt;
                    $refundedhit +=$list->RefundedHit;
                    $frefundedamt +=$list->RefundedAmt;
                    $pendingamt +=$list->PendingAmt;
                    $pendinghit +=$list->PendingHit;
                    $totalcomm +=$list->Comm;
                    $i++;
                }
                $output .= '</tbody><tfoot>
                    <tr>
                        <td class="text-center font-weight-bold">Total</td>
                        <td class="text-center font-weight-bold"> ' . $totalhit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float)$totalamt, 2) . '</td>
                        <td class="text-center font-weight-bold"> ' . $successhit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float)$successamt, 2) . '</td>
                        <td class="text-center font-weight-bold"> ' . $failedhit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float)$failedamt, 2) . '</td>
                        <td class="text-center font-weight-bold"> ' . $refundedhit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float)$frefundedamt, 2) . '</td>
                        <td class="text-center font-weight-bold"> ' . $pendinghit . ' /<i class="las la-rupee-sign"></i>  ' . number_format((float)$pendingamt, 2) . '</td>
                        <td class="text-center font-weight-bold"><i class="las la-rupee-sign"></i> ' . number_format((float)$totalcomm, 2) . '</td>
                    </tr>
                </tfoot>';
                $output .= '</table>';
                return $output;
                //echo '<h4 class="text-center text-secondary my-3">record found</h4>';
            }else{
                echo '<h4 class="text-center text-secondary my-3">No record found</h4>';
            }
        //return $reports;
    }


    public function providerSaleReports(Request $post)
    {
        return view('admin.admin-reports.provider-sale-report');
    }


    public function providerSaleReportsList(Request $post) {
        $start_date2 = Carbon::now()->format('Y-m-d').' 00:00:00';
        $end_date2 = Carbon::now()->format('Y-m-d').' 23:59:59';
        $where = "";
        if($post->from_date){
            $start_date2 = $post->from_date.' 00:00:00';
            $end_date2 = $post->to_date.' 23:59:59';
            if($post->user_id!=0){
                $where = " AND u.id = '".$post->user_id."'"; 
            }
        }
        // echo "<pre>";print_r($start_date2);//die;
        // echo "<pre>";print_r($end_date2);die;
        $query2 =  "SELECT 
        p.provider_name,
        s.service_name,
        r.user_id as id,    
        COUNT(IF(r.status = 'Pending', 1, NULL)) 'PendingHit',
        COUNT(IF(r.status = 'Failed', 1, NULL)) 'FailedHit',
        COUNT(IF(r.status = 'Success', 1, NULL)) 'SuccessHit',
        COUNT(IF(r.status = 'Refunded', 1, NULL)) 'RefundedHit',
        COUNT(r.id) 'TotalHit' ,
        SUM(CASE WHEN r.status = 'Pending' THEN total_amount ELSE 0 END) PendingAmt,
        SUM(CASE WHEN r.status = 'Failed' THEN total_amount ELSE 0 END) FailedAmt,
        SUM(CASE WHEN r.status = 'Success' THEN total_amount ELSE 0 END) SuccessAmt,
        SUM(CASE WHEN r.status = 'Refunded' THEN total_amount ELSE 0 END) RefundedAmt,
        SUM(r.total_amount) 'TotalAmt',
        SUM(CASE WHEN r.status = 'Success' THEN commission ELSE 0 END) Comm
        FROM `reports` as r 
        JOIN services as s ON s.id=r.service_id 
        JOIN users as u ON u.id=r.user_id 
        JOIN providers as p ON p.id=r.provider_id 
        WHERE r.created_at between '$start_date2' AND '$end_date2' AND transaction_type IN ('Recharge', 'Bill Pay') $where
        group by p.provider_name,r.user_id,s.service_name";
       //echo "<pre>";print_r($query2);die;
        $reports = DB::select($query2);
        $output = '';
        if (count($reports) > 0) {
            $output .= '<table id="scroll-vertical" class="table table-bordered dt-responsive nowrap align-middle mdl-data-table" style="width:100%;text-transform: uppercase;">
            <thead>
            <tr>
                <th>Provider Details</th>
                <th>Qty/Total Amount</th>
                <th>Qty/Success Amount</th>
                <th>Qty/Failed Amount</th>
                <th>Qty/Refunded Amount</th>
                <th>Qty/Pending Amount</th>
                <th>Commission</th>
            </tr>
            </thead>
            <tbody>';
            $i=1;
            $totalhit = 0;
            $totalamt = 0;
            $successhit = 0;
            $successamt = 0;
            $failedhit = 0;
            $failedamt = 0;
            $refundedhit = 0;
            $frefundedamt = 0;
            $pendingamt = 0;
            $pendinghit = 0;
            $totalcomm = 0;
            foreach ($reports as $list) {
                $output .= '<tr>
                    <td class="text-center font-weight-bold">' . $list->provider_name . ' - ' . $list->service_name . '</td>
                    <td class="text-center font-weight-bold">' . $list->TotalHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->TotalAmt, 2) . '</td>
                    <td class="text-center font-weight-bold">' . $list->SuccessHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->SuccessAmt, 2). '</td>
                    <td class="text-center font-weight-bold">' . $list->FailedHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->FailedAmt, 2) . '</td>
                    <td class="text-center font-weight-bold">' . $list->RefundedHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->RefundedAmt, 2) . '</td>
                    <td class="text-center font-weight-bold">' . $list->PendingHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->PendingAmt, 2) . '</td>
                    <td class="text-center font-weight-bold"><i class="las la-rupee-sign"></i> ' . number_format((float) $list->Comm, 2) . '</td>
                </tr>';
                $totalhit +=$list->TotalHit;
                $totalamt +=$list->TotalAmt;
                $successhit +=$list->SuccessHit;
                $successamt +=$list->SuccessAmt;
                $failedhit +=$list->FailedHit;
                $failedamt +=$list->FailedAmt;
                $refundedhit +=$list->RefundedHit;
                $frefundedamt +=$list->RefundedAmt;
                $pendingamt +=$list->PendingAmt;
                $pendinghit +=$list->PendingHit;
                $totalcomm +=$list->Comm;
                $i++;
            }
            $output .= '</tbody><tfoot>
                <tr>
                    <td class="text-center font-weight-bold">Total</td>
                    <td class="text-center font-weight-bold"> ' . $totalhit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float)$totalamt, 2) . '</td>
                    <td class="text-center font-weight-bold"> ' . $successhit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float)$successamt, 2) . '</td>
                    <td class="text-center font-weight-bold"> ' . $failedhit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float)$failedamt, 2) . '</td>
                    <td class="text-center font-weight-bold"> ' . $refundedhit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float)$frefundedamt, 2) . '</td>
                    <td class="text-center font-weight-bold"> ' . $pendinghit . ' /<i class="las la-rupee-sign"></i>  ' . number_format((float)$pendingamt, 2) . '</td>
                    <td class="text-center font-weight-bold"><i class="las la-rupee-sign"></i> ' . number_format((float)$totalcomm, 2) . '</td>
                </tr>
            </tfoot>';
            $output .= '</table>';
            return $output;
            //echo '<h4 class="text-center text-secondary my-3">record found</h4>';
        }else{
            echo '<h4 class="text-center text-secondary my-3">No record found</h4>';
        }
    }

    public function apiSaleReports(Request $post)
    {
        return view('admin.admin-reports.api-sale-report');
    }

    public function apiList()
    {
        $apis = DB::table('apis')->select('id','api_name')->where('deleted_at', '!=' , 1)->where('status',1)->get();
        if($apis){
            $data['type'] = 'success';
            $data['message'] = "Get sucessfuly";
            $data['apis'] = $apis;
        } else {
            $data['type'] = 'error';
            $data['message'] = "Something went wrong!";
        }
        return $data;
    }

    public function apiSaleReportsList(Request $post) {
        $start_date2 = Carbon::now()->format('Y-m-d').' 00:00:00';
        $end_date2 = Carbon::now()->format('Y-m-d').' 23:59:59';
        $where = "";
        if($post->from_date){
            $start_date2 = $post->from_date.' 00:00:00';
            $end_date2 = $post->to_date.' 23:59:59';
            if($post->user_id!=0){
                $where = " AND a.id = '".$post->api_id."'"; 
            }
        }
        // echo "<pre>";print_r($start_date2);//die;
        // echo "<pre>";print_r($end_date2);die;
        $query2 =  "SELECT 
        a.api_name,
        COUNT(IF(r.status = 'Pending', 1, NULL)) 'PendingHit',
        COUNT(IF(r.status = 'Failed', 1, NULL)) 'FailedHit',
        COUNT(IF(r.status = 'Success', 1, NULL)) 'SuccessHit',
        COUNT(IF(r.status = 'Refunded', 1, NULL)) 'RefundedHit',
        COUNT(r.id) 'TotalHit' ,
        SUM(CASE WHEN r.status = 'Pending' THEN total_amount ELSE 0 END) PendingAmt,
        SUM(CASE WHEN r.status = 'Failed' THEN total_amount ELSE 0 END) FailedAmt,
        SUM(CASE WHEN r.status = 'Success' THEN total_amount ELSE 0 END) SuccessAmt,
        SUM(CASE WHEN r.status = 'Refunded' THEN total_amount ELSE 0 END) RefundedAmt,
        SUM(r.total_amount) 'TotalAmt',
        SUM(CASE WHEN r.status = 'Success' THEN commission ELSE 0 END) Comm
        FROM `reports` as r JOIN users as u ON u.id=r.user_id 
        JOIN apis as a ON a.id=r.api_id   
        WHERE r.created_at between '$start_date2' AND '$end_date2' AND transaction_type IN ('Recharge', 'Bill Pay') $where
        group by a.api_name";
       //echo "<pre>";print_r($query2);die;
        $reports = DB::select($query2);
        $output = '';
        if (count($reports) > 0) {
            $output .= '<table id="scroll-vertical" class="table table-bordered dt-responsive nowrap align-middle mdl-data-table" style="width:100%;text-transform: uppercase;">
            <thead>
            <tr>
                <th>API Details</th>
                <th>Qty/Total Amount</th>
                <th>Qty/Success Amount</th>
                <th>Qty/Failed Amount</th>
                <th>Qty/Refunded Amount</th>
                <th>Qty/Pending Amount</th>
                <th>Commission</th>
            </tr>
            </thead>
            <tbody>';
            $i=1;
            $totalhit = 0;
            $totalamt = 0;
            $successhit = 0;
            $successamt = 0;
            $failedhit = 0;
            $failedamt = 0;
            $refundedhit = 0;
            $frefundedamt = 0;
            $pendingamt = 0;
            $pendinghit = 0;
            $totalcomm = 0;
            foreach ($reports as $list) {
                $output .= '<tr>
                    <td class="text-center font-weight-bold">' . $list->api_name . '</td>
                    <td class="text-center font-weight-bold">' . $list->TotalHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->TotalAmt, 2) . '</td>
                    <td class="text-center font-weight-bold">' . $list->SuccessHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->SuccessAmt, 2). '</td>
                    <td class="text-center font-weight-bold">' . $list->FailedHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->FailedAmt, 2) . '</td>
                    <td class="text-center font-weight-bold">' . $list->RefundedHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->RefundedAmt, 2) . '</td>
                    <td class="text-center font-weight-bold">' . $list->PendingHit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float) $list->PendingAmt, 2) . '</td>
                    <td class="text-center font-weight-bold"><i class="las la-rupee-sign"></i> ' . number_format((float) $list->Comm, 2) . '</td>
                </tr>';
                $totalhit +=$list->TotalHit;
                $totalamt +=$list->TotalAmt;
                $successhit +=$list->SuccessHit;
                $successamt +=$list->SuccessAmt;
                $failedhit +=$list->FailedHit;
                $failedamt +=$list->FailedAmt;
                $refundedhit +=$list->RefundedHit;
                $frefundedamt +=$list->RefundedAmt;
                $pendingamt +=$list->PendingAmt;
                $pendinghit +=$list->PendingHit;
                $totalcomm +=$list->Comm;
                $i++;
            }
            $output .= '</tbody><tfoot>
                <tr>
                    <td class="text-center font-weight-bold">Total</td>
                    <td class="text-center font-weight-bold"> ' . $totalhit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float)$totalamt, 2) . '</td>
                    <td class="text-center font-weight-bold"> ' . $successhit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float)$successamt, 2) . '</td>
                    <td class="text-center font-weight-bold"> ' . $failedhit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float)$failedamt, 2) . '</td>
                    <td class="text-center font-weight-bold"> ' . $refundedhit . ' / <i class="las la-rupee-sign"></i> ' . number_format((float)$frefundedamt, 2) . '</td>
                    <td class="text-center font-weight-bold"> ' . $pendinghit . ' /<i class="las la-rupee-sign"></i>  ' . number_format((float)$pendingamt, 2) . '</td>
                    <td class="text-center font-weight-bold"><i class="las la-rupee-sign"></i> ' . number_format((float)$totalcomm, 2) . '</td>
                </tr>
            </tfoot>';
            $output .= '</table>';
            return $output;
            //echo '<h4 class="text-center text-secondary my-3">record found</h4>';
        }else{
            echo '<h4 class="text-center text-secondary my-3">No record found</h4>';
        }
    }


    public function apiLogReports(Request $post)
    {
        return view('admin.admin-reports.api-log-report');
    }


    public function apiLogReportsList(Request $post)
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
        if($post->from_date){
            $from_date = $post->from_date." 00:00:00";
            $to_date = $post->to_date." 23:59:59";
            $table = "apilogs";
        }else{
            $from_date = Carbon::today()->format('Y-m-d')." 00:00:00";
            $to_date = Carbon::today()->format('Y-m-d')." 23:59:59";
            $table = "apilogs";
        }
        
        $start= ($page-1) * $limit;
        $total_row = DB::table($table)
            ->whereBetween('created_at', [$from_date,$to_date])
            ->where('txnid', 'like', '%' . $post->order_id . '%')
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
            ->where('txnid', 'like', '%' . $post->order_id . '%')
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
            $output .= '<table class="table table-bordered table-nowrap" id="pagination_table"><thead>
              <tr>
                <th>ID</th>
                <th>Order Id</th>
                <th>Date & Time</th>
                <th>Req Type</th>
                <th>Req Url</th>
                <th>Req Header</th>
                <th>Req Post</th>
                <th>Response</th>
              </tr>
            </thead>
            <tbody>';
            $i=$start + 1;
			foreach ($list as $list) {
				$output .= '<tr>
                <td>' . $i . '</td>
                <td>' . $list->txnid . '</td>
                <td>' . $list->created_at . '</td>
                <td>' . $list->modal . '</td>
                <td>' . $list->url . '</td>
                <td>' . $list->header . '</td>
                <td>' . $list->request . '</td>
                <td>' . $list->response . '</td>
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
