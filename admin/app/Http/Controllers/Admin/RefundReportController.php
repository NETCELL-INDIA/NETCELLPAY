<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundReportController extends Controller
{
    public function index()
    {
        $apis = DB::table('apis')->orderBy('api_name')->get(['id', 'api_name']);
        $providers = DB::table('providers')->orderBy('provider_name')->get(['id', 'provider_name']);

        return view('admin.recharge-reports.refund-report', compact('apis', 'providers'));
    }

    private function baseQuery(Request $request)
    {
        $q = DB::table('reports as r')
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->leftJoin('providers as p', 'p.id', '=', 'r.provider_id')
            ->leftJoin('apis as a', 'a.id', '=', 'r.api_id')
            ->where(function ($w) {
                $w->whereIn('r.status', ['Refunded', 'Refund'])
                    ->orWhere('r.transaction_type', 'Refund');
            });

        $from = $request->from_date ?: Carbon::today()->format('Y-m-d');
        $to = $request->to_date ?: Carbon::today()->format('Y-m-d');
        $q->where(function ($w) use ($from, $to) {
            $w->whereBetween('r.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->orWhereBetween('r.transaction_date', [$from . ' 00:00:00', $to . ' 23:59:59']);
        });

        if ($request->api_id) {
            $q->where('r.api_id', (int) $request->api_id);
        }
        if ($request->provider_id) {
            $q->where('r.provider_id', (int) $request->provider_id);
        }
        if ($request->user_id) {
            $q->where('r.user_id', (int) $request->user_id);
        }

        return $q;
    }

    public function list(Request $request)
    {
        $limit = (int) ($request->show ?: 10);
        if (!in_array($limit, [10, 25, 50, 100], true)) {
            $limit = 10;
        }
        $page = max(1, (int) ($request->page ?: 1));
        $offset = ($page - 1) * $limit;

        $base = $this->baseQuery($request);
        $total = (clone $base)->count();

        // Summary across related statuses in same date filters (like screenshot pills)
        $summaryBase = DB::table('reports as r')->whereIn('r.transaction_type', ['Recharge', 'Bill Pay', 'Bill Payment', 'Refund']);
        $from = $request->from_date ?: Carbon::today()->format('Y-m-d');
        $to = $request->to_date ?: Carbon::today()->format('Y-m-d');
        $summaryBase->where(function ($w) use ($from, $to) {
            $w->whereBetween('r.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->orWhereBetween('r.transaction_date', [$from . ' 00:00:00', $to . ' 23:59:59']);
        });
        if ($request->api_id) {
            $summaryBase->where('r.api_id', (int) $request->api_id);
        }
        if ($request->provider_id) {
            $summaryBase->where('r.provider_id', (int) $request->provider_id);
        }
        if ($request->user_id) {
            $summaryBase->where('r.user_id', (int) $request->user_id);
        }
        $summaryRows = (clone $summaryBase)->selectRaw("
            SUM(CASE WHEN r.status = 'Success' THEN r.amount ELSE 0 END) as success_amt,
            SUM(CASE WHEN r.status = 'Success' THEN 1 ELSE 0 END) as success_cnt,
            SUM(CASE WHEN r.status IN ('Pending','Under Proces','Under Process') THEN r.amount ELSE 0 END) as pending_amt,
            SUM(CASE WHEN r.status IN ('Pending','Under Proces','Under Process') THEN 1 ELSE 0 END) as pending_cnt,
            SUM(CASE WHEN r.status IN ('Failed','Failure') THEN r.amount ELSE 0 END) as failure_amt,
            SUM(CASE WHEN r.status IN ('Failed','Failure') THEN 1 ELSE 0 END) as failure_cnt,
            SUM(CASE WHEN r.status IN ('Refunded','Refund') THEN r.amount ELSE 0 END) as refunded_amt,
            SUM(CASE WHEN r.status IN ('Refunded','Refund') THEN 1 ELSE 0 END) as refunded_cnt
        ")->first();

        $reports = (clone $base)
            ->select('r.*', 'u.outlet_name', 'u.first_name', 'u.mobile_number', 'p.provider_name', 'a.api_name')
            ->orderByDesc('r.id')->offset($offset)->limit($limit)->get();

        $rows = '';
        if ($reports->count() > 0) {
            foreach ($reports as $list) {
                $user = trim(($list->outlet_name ?: $list->first_name ?: 'User') . ' / ' . ($list->mobile_number ?: '-') . ' / ID:' . ($list->user_id ?: '-'));
                $rows .= '<tr>
                    <td><strong>' . e($list->order_id ?: ('R' . $list->id)) . '</strong><br><small class="text-muted">#' . e($list->id) . '</small></td>
                    <td>' . e($list->transaction_date ?: $list->created_at) . '</td>
                    <td>' . e($user) . '</td>
                    <td>' . e($list->provider_name ?: '-') . '</td>
                    <td>' . e($list->number ?: '-') . '</td>
                    <td>₹' . number_format((float) $list->amount, 2) . '</td>
                    <td><span class="badge bg-primary">' . e(strtoupper($list->status)) . '</span></td>
                    <td><small>' . e(($list->operator_id ?: '-') . ' / ' . ($list->request_order_id ?: '-')) . '</small></td>
                    <td>' . e($list->api_name ?: '-') . '</td>
                </tr>';
            }
        } else {
            $rows = '<tr><td colspan="9" class="text-center text-muted py-4">No data available in table</td></tr>';
        }

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
                'from' => $total ? ($offset + 1) : 0,
                'to' => min($offset + $limit, $total),
                'last_page' => max(1, (int) ceil($total / max($limit, 1))),
            ],
        ]);
    }

    public function download(Request $request)
    {
        $reports = $this->baseQuery($request)
            ->select('r.*', 'u.outlet_name', 'u.first_name', 'u.mobile_number', 'p.provider_name', 'a.api_name')
            ->orderByDesc('r.id')->limit(5000)->get();

        $filename = 'refund-report-' . date('Ymd-His') . '.csv';
        return response()->stream(function () use ($reports) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Recharge ID', 'Date Time', 'User', 'Operator', 'Number', 'Amount', 'Status', 'Opt ID', 'Client ID', 'API']);
            foreach ($reports as $list) {
                fputcsv($out, [
                    $list->order_id ?: ('R' . $list->id),
                    $list->transaction_date ?: $list->created_at,
                    trim(($list->outlet_name ?: $list->first_name ?: 'User') . ' / ' . ($list->mobile_number ?: '')),
                    $list->provider_name,
                    $list->number,
                    $list->amount,
                    $list->status,
                    $list->operator_id,
                    $list->request_order_id,
                    $list->api_name,
                ]);
            }
            fclose($out);
        }, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""]);
    }
}
