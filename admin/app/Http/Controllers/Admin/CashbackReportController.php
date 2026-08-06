<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashbackReportController extends Controller
{
    public function index()
    {
        $providers = DB::table('providers')->orderBy('provider_name')->get(['id', 'provider_name']);

        return view('admin.recharge-reports.cashback-report', compact('providers'));
    }

    private function baseQuery(Request $request)
    {
        $q = DB::table('reports as r')
            ->leftJoin('providers as p', 'p.id', '=', 'r.provider_id')
            ->whereIn('r.transaction_type', ['Recharge', 'Bill Pay', 'Bill Payment'])
            ->where('r.status', 'Success');

        $from = $request->from_date ?: Carbon::today()->format('Y-m-d');
        $to = $request->to_date ?: Carbon::today()->format('Y-m-d');
        $q->where(function ($w) use ($from, $to) {
            $w->whereBetween('r.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->orWhereBetween('r.transaction_date', [$from . ' 00:00:00', $to . ' 23:59:59']);
        });

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

        $allRows = $this->baseQuery($request)
            ->select(
                'p.provider_name',
                DB::raw('COUNT(r.id) as txns'),
                DB::raw('SUM(r.amount) as mrp'),
                DB::raw('SUM(COALESCE(r.total_amount, r.amount)) as received'),
                DB::raw('SUM(COALESCE(r.commission, 0)) as cashback')
            )
            ->groupBy('r.provider_id', 'p.provider_name')
            ->orderBy('p.provider_name')
            ->get()
            ->map(function ($row) {
                $mrp = (float) $row->mrp;
                $received = (float) $row->received;
                $cashback = (float) $row->cashback;
                // Profit = MRP - Received + Cashback perspective for retailer:
                // Received is debit from wallet (total_amount), cashback is commission returned
                $profit = $cashback;
                if ($mrp > 0 && $received > 0 && abs($received - $mrp) > 0.001) {
                    $profit = ($mrp - $received) + $cashback;
                }
                $row->profit_amount = $profit;
                $row->profit_pct = $mrp > 0 ? ($profit / $mrp) * 100 : 0;
                return $row;
            });

        $total = $allRows->count();
        $pageRows = $allRows->slice($offset, $limit)->values();

        $sumTxns = (int) $allRows->sum('txns');
        $sumMrp = (float) $allRows->sum('mrp');
        $sumReceived = (float) $allRows->sum('received');
        $sumCashback = (float) $allRows->sum('cashback');
        $sumProfit = (float) $allRows->sum('profit_amount');

        $html = '';
        if ($pageRows->count() > 0) {
            $i = $offset + 1;
            foreach ($pageRows as $row) {
                $html .= '<tr>
                    <td>' . $i++ . '</td>
                    <td>' . e($row->provider_name ?: '-') . '</td>
                    <td>' . (int) $row->txns . '</td>
                    <td>' . number_format((float) $row->mrp, 2) . '</td>
                    <td>' . number_format((float) $row->received, 2) . '</td>
                    <td>' . number_format((float) $row->cashback, 2) . '</td>
                    <td>' . number_format((float) $row->profit_amount, 2) . '</td>
                    <td>' . number_format((float) $row->profit_pct, 2) . '%</td>
                </tr>';
            }
            $html .= '<tr class="fw-bold table-light">
                <td></td>
                <td>Total:</td>
                <td>' . $sumTxns . '</td>
                <td>' . number_format($sumMrp, 2) . '</td>
                <td>' . number_format($sumReceived, 2) . '</td>
                <td>' . number_format($sumCashback, 2) . '</td>
                <td>' . number_format($sumProfit, 2) . '</td>
                <td></td>
            </tr>';
        } else {
            $html = '<tr><td colspan="8" class="text-center text-muted py-4">No data available in table</td></tr>';
        }

        return response()->json([
            'type' => 'success',
            'rows' => $html,
            'summary' => [
                'mrp' => number_format($sumMrp, 2),
                'received' => number_format($sumReceived, 2),
                'cashback' => number_format($sumCashback, 2),
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
        $rows = $this->baseQuery($request)
            ->select(
                'p.provider_name',
                DB::raw('COUNT(r.id) as txns'),
                DB::raw('SUM(r.amount) as mrp'),
                DB::raw('SUM(COALESCE(r.total_amount, r.amount)) as received'),
                DB::raw('SUM(COALESCE(r.commission, 0)) as cashback')
            )
            ->groupBy('r.provider_id', 'p.provider_name')
            ->orderBy('p.provider_name')
            ->get();

        $filename = 'cashback-report-' . date('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SR NO', 'OPERATOR', 'TXNS', 'MRP', 'RECEIVED', 'CASHBACK', 'PROFIT AMOUNT', 'PROFIT (%)']);
            $i = 1;
            foreach ($rows as $row) {
                $mrp = (float) $row->mrp;
                $cashback = (float) $row->cashback;
                $profit = $cashback;
                $pct = $mrp > 0 ? ($profit / $mrp) * 100 : 0;
                fputcsv($out, [
                    $i++,
                    $row->provider_name,
                    (int) $row->txns,
                    number_format($mrp, 2, '.', ''),
                    number_format((float) $row->received, 2, '.', ''),
                    number_format($cashback, 2, '.', ''),
                    number_format($profit, 2, '.', ''),
                    number_format($pct, 2, '.', ''),
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
