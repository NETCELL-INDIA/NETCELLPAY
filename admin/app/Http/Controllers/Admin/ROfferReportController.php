<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ROfferReportController extends Controller
{
    public function index()
    {
        $apis = DB::table('apis')->orderBy('api_name')->get(['id', 'api_name']);
        $providers = DB::table('providers')->orderBy('provider_name')->get(['id', 'provider_name']);
        $circles = DB::table('states')->orderBy('state_name')->get(['id', 'state_name']);

        return view('admin.recharge-reports.r-offer-report', compact('apis', 'providers', 'circles'));
    }

    private function base(Request $request)
    {
        $q = DB::table('reports as r')
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->leftJoin('providers as p', 'p.id', '=', 'r.provider_id')
            ->leftJoin('apis as a', 'a.id', '=', 'r.api_id')
            ->leftJoin('states as st', 'st.id', '=', 'r.state_id')
            ->whereIn('r.transaction_type', ['Recharge', 'Bill Pay', 'Bill Payment']);

        $from = $request->from_date ?: Carbon::today()->format('Y-m-d');
        $to = $request->to_date ?: Carbon::today()->format('Y-m-d');
        $q->where(function ($w) use ($from, $to) {
            $w->whereBetween('r.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->orWhereBetween('r.transaction_date', [$from . ' 00:00:00', $to . ' 23:59:59']);
        });

        if ($request->api_id) {
            $q->where('r.api_id', (int) $request->api_id);
        }
        if ($request->user_id) {
            $q->where('r.user_id', (int) $request->user_id);
        }
        if ($request->provider_id) {
            $q->where('r.provider_id', (int) $request->provider_id);
        }
        if ($request->circle_id) {
            $q->where('r.state_id', (int) $request->circle_id);
        }
        if ($request->status) {
            $status = $request->status;
            if ($status === 'Failure') {
                $q->whereIn('r.status', ['Failed', 'Failure']);
            } else {
                $q->where('r.status', $status);
            }
        }
        if ($request->number) {
            $q->where('r.number', 'like', '%' . trim($request->number) . '%');
        }
        if ($request->amount !== null && $request->amount !== '') {
            $q->where('r.amount', $request->amount);
        }
        if ($request->roffer_type && $request->roffer_type !== 'All') {
            $q->where(function ($w) use ($request) {
                $w->where('r.remark', 'like', '%' . $request->roffer_type . '%')
                    ->orWhere('r.callback_response', 'like', '%' . $request->roffer_type . '%');
            });
        }

        return $q;
    }

    public function list(Request $request)
    {
        $limit = in_array((int) $request->show, [10, 25, 50, 100], true) ? (int) $request->show : 10;
        $page = max(1, (int) ($request->page ?: 1));
        $offset = ($page - 1) * $limit;

        $base = $this->base($request);
        $total = (clone $base)->count();
        $summary = (clone $base)->selectRaw("
            SUM(CASE WHEN r.status='Success' THEN r.amount ELSE 0 END) success_amt,
            SUM(CASE WHEN r.status='Success' THEN 1 ELSE 0 END) success_cnt,
            SUM(CASE WHEN r.status IN ('Pending','Under Process','Under Proces') THEN r.amount ELSE 0 END) pending_amt,
            SUM(CASE WHEN r.status IN ('Pending','Under Process','Under Proces') THEN 1 ELSE 0 END) pending_cnt,
            SUM(CASE WHEN r.status IN ('Failed','Failure') THEN r.amount ELSE 0 END) failure_amt,
            SUM(CASE WHEN r.status IN ('Failed','Failure') THEN 1 ELSE 0 END) failure_cnt,
            SUM(CASE WHEN r.status IN ('Refunded','Refund') THEN r.amount ELSE 0 END) refunded_amt,
            SUM(CASE WHEN r.status IN ('Refunded','Refund') THEN 1 ELSE 0 END) refunded_cnt
        ")->first();

        $reports = (clone $base)->select('r.*', 'u.outlet_name', 'u.first_name', 'u.mobile_number', 'p.provider_name', 'a.api_name', 'st.state_name as circle_name')
            ->orderByDesc('r.id')->offset($offset)->limit($limit)->get();

        $html = '';
        if ($reports->count()) {
            foreach ($reports as $r) {
                $user = trim(($r->outlet_name ?: $r->first_name ?: 'User') . ' / ' . ($r->mobile_number ?: '-') . ' / ID:' . ($r->user_id ?: '-'));
                $rofrCheck = (stripos((string) $r->remark . $r->callback_response, 'roffer') !== false || stripos((string) $r->remark . $r->callback_response, 'rofr') !== false) ? 'Yes' : 'No';
                $rofrRecv = '-';
                if (preg_match('/roffer[^\d]*(\d+(?:\.\d+)?)/i', (string) ($r->callback_response . ' ' . $r->remark), $m)) {
                    $rofrRecv = $m[1];
                }
                $badge = $r->status === 'Success' ? 'success' : (in_array($r->status, ['Failed', 'Failure']) ? 'danger' : (in_array($r->status, ['Refunded', 'Refund']) ? 'primary' : 'warning'));
                $html .= '<tr>
                    <td><strong>' . e($r->order_id ?: ('R' . $r->id)) . '</strong></td>
                    <td>' . e($r->transaction_date ?: $r->created_at) . '</td>
                    <td>' . e($user) . '</td>
                    <td>' . e($r->provider_name ?: '-') . '</td>
                    <td>' . e($r->circle_name ?: '-') . '</td>
                    <td>' . e($r->api_name ?: '-') . '</td>
                    <td>' . e($r->number ?: '-') . '</td>
                    <td>₹' . number_format((float) $r->amount, 2) . '</td>
                    <td><span class="badge bg-' . $badge . '">' . e(strtoupper($r->status)) . '</span></td>
                    <td>' . e($rofrCheck) . '</td>
                    <td>' . e($rofrRecv) . '</td>
                </tr>';
            }
        } else {
            $html = '<tr><td colspan="11" class="text-center text-muted py-4">No data available in table</td></tr>';
        }

        return response()->json([
            'type' => 'success',
            'rows' => $html,
            'summary' => [
                'success_amt' => number_format((float) ($summary->success_amt ?? 0), 2),
                'success_cnt' => (int) ($summary->success_cnt ?? 0),
                'pending_amt' => number_format((float) ($summary->pending_amt ?? 0), 2),
                'pending_cnt' => (int) ($summary->pending_cnt ?? 0),
                'failure_amt' => number_format((float) ($summary->failure_amt ?? 0), 2),
                'failure_cnt' => (int) ($summary->failure_cnt ?? 0),
                'refunded_amt' => number_format((float) ($summary->refunded_amt ?? 0), 2),
                'refunded_cnt' => (int) ($summary->refunded_cnt ?? 0),
            ],
            'pagination' => [
                'page' => $page, 'show' => $limit, 'total' => $total,
                'from' => $total ? $offset + 1 : 0, 'to' => min($offset + $limit, $total),
                'last_page' => max(1, (int) ceil($total / max($limit, 1))),
            ],
        ]);
    }

    public function download(Request $request)
    {
        $reports = $this->base($request)->select('r.*', 'u.outlet_name', 'u.first_name', 'u.mobile_number', 'p.provider_name', 'a.api_name', 'st.state_name as circle_name')
            ->orderByDesc('r.id')->limit(5000)->get();
        $filename = 'r-offer-report-' . date('Ymd-His') . '.csv';
        return response()->stream(function () use ($reports) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['RECHARGE ID', 'DATE', 'USER', 'OPERATOR', 'CIRCLE', 'API', 'NUMBER', 'AMOUNT', 'STATUS', 'ROFR CHECK', 'ROFR RECV']);
            foreach ($reports as $r) {
                fputcsv($out, [
                    $r->order_id, $r->transaction_date ?: $r->created_at,
                    trim(($r->outlet_name ?: $r->first_name ?: 'User') . '/' . ($r->mobile_number ?: '')),
                    $r->provider_name, $r->circle_name, $r->api_name, $r->number, $r->amount, $r->status, 'No', '-',
                ]);
            }
            fclose($out);
        }, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""]);
    }
}
