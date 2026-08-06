<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiReportController extends Controller
{
    public function index()
    {
        $apis = DB::table('apis')->orderBy('api_name')->get(['id', 'api_name']);
        $providers = DB::table('providers')->orderBy('provider_name')->get(['id', 'provider_name']);
        $circles = DB::table('states')->orderBy('state_name')->get(['id', 'state_name']);

        return view('admin.recharge-reports.api-report', compact('apis', 'providers', 'circles'));
    }

    private function baseQuery(Request $request)
    {
        $q = DB::table('reports as r')
            ->leftJoin('apis as a', 'a.id', '=', 'r.api_id')
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
        if ($request->provider_id) {
            $q->where('r.provider_id', (int) $request->provider_id);
        }
        if ($request->circle_id) {
            $q->where('r.state_id', (int) $request->circle_id);
        }

        return $q;
    }

    public function list(Request $request)
    {
        $rows = $this->baseQuery($request)
            ->select(
                'a.id as api_id',
                'a.api_name',
                DB::raw("SUM(CASE WHEN r.status = 'Success' THEN 1 ELSE 0 END) as transactions"),
                DB::raw("SUM(CASE WHEN r.status = 'Success' THEN r.amount ELSE 0 END) as mrp"),
                DB::raw("SUM(CASE WHEN r.status = 'Success' THEN COALESCE(r.commission,0) ELSE 0 END) as margin"),
                DB::raw("SUM(CASE WHEN r.status = 'Success' THEN GREATEST(COALESCE(r.total_amount,0)-COALESCE(r.amount,0),0) ELSE 0 END) as surcharge"),
                DB::raw("SUM(CASE WHEN r.status IN ('Refunded','Refund') THEN r.amount ELSE 0 END) as refund_mrp")
            )
            ->groupBy('a.id', 'a.api_name')
            ->orderBy('a.api_name')
            ->get();

        $sumTxn = 0; $sumMrp = 0; $sumMargin = 0; $sumSurcharge = 0; $sumRefund = 0;
        $html = '';
        if ($rows->count() > 0) {
            $i = 1;
            foreach ($rows as $row) {
                $txn = (int) $row->transactions;
                $mrp = (float) $row->mrp;
                $margin = (float) $row->margin;
                $surcharge = (float) $row->surcharge;
                $refund = (float) $row->refund_mrp;
                $avgMargin = $txn > 0 ? $margin / $txn : 0;
                $avgSurcharge = $txn > 0 ? $surcharge / $txn : 0;
                $totalMrp = $mrp; // success MRP; refund shown separately
                $sumTxn += $txn; $sumMrp += $mrp; $sumMargin += $margin; $sumSurcharge += $surcharge; $sumRefund += $refund;

                $html .= '<tr>
                    <td>' . $i++ . '</td>
                    <td>' . e($row->api_name ?: 'NO API') . '</td>
                    <td>' . $txn . '</td>
                    <td>' . number_format($mrp, 2) . '</td>
                    <td>' . number_format($margin, 2) . '</td>
                    <td>' . number_format($avgMargin, 2) . '</td>
                    <td>' . number_format($surcharge, 2) . '</td>
                    <td>' . number_format($avgSurcharge, 2) . '</td>
                    <td>' . number_format($refund, 2) . '</td>
                    <td>' . number_format($totalMrp, 2) . '</td>
                    <td><button type="button" class="btn btn-sm btn-outline-primary btn-view-api" data-api="' . e($row->api_id) . '">View</button></td>
                </tr>';
            }
            $avgM = $sumTxn > 0 ? $sumMargin / $sumTxn : 0;
            $avgS = $sumTxn > 0 ? $sumSurcharge / $sumTxn : 0;
            $html .= '<tr class="fw-bold table-light">
                <td></td><td>Total:</td>
                <td>' . $sumTxn . '</td>
                <td>' . number_format($sumMrp, 2) . '</td>
                <td>' . number_format($sumMargin, 2) . '</td>
                <td>' . number_format($avgM, 2) . '</td>
                <td>' . number_format($sumSurcharge, 2) . '</td>
                <td>' . number_format($avgS, 2) . '</td>
                <td>' . number_format($sumRefund, 2) . '</td>
                <td>' . number_format($sumMrp, 2) . '</td>
                <td></td>
            </tr>';
        } else {
            $html = '<tr><td colspan="11" class="text-center text-muted py-4">No data available in table</td></tr>
            <tr class="fw-bold table-light"><td></td><td>Total:</td><td>0</td><td>0.00</td><td>0.00</td><td>0.00</td><td>0.00</td><td>0.00</td><td>0.00</td><td>0.00</td><td></td></tr>';
        }

        return response()->json(['type' => 'success', 'rows' => $html]);
    }

    public function download(Request $request)
    {
        $request->merge([]);
        $res = $this->list($request);
        $data = $res->getData(true);
        // simpler CSV rebuild
        $rows = $this->baseQuery($request)
            ->select('a.api_name', DB::raw("SUM(CASE WHEN r.status='Success' THEN 1 ELSE 0 END) as transactions"), DB::raw("SUM(CASE WHEN r.status='Success' THEN r.amount ELSE 0 END) as mrp"), DB::raw("SUM(CASE WHEN r.status='Success' THEN COALESCE(r.commission,0) ELSE 0 END) as margin"), DB::raw("SUM(CASE WHEN r.status='Success' THEN GREATEST(COALESCE(r.total_amount,0)-COALESCE(r.amount,0),0) ELSE 0 END) as surcharge"), DB::raw("SUM(CASE WHEN r.status IN ('Refunded','Refund') THEN r.amount ELSE 0 END) as refund_mrp"))
            ->groupBy('a.id', 'a.api_name')->orderBy('a.api_name')->get();

        $filename = 'api-report-' . date('Ymd-His') . '.csv';
        return response()->stream(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SR NO', 'API NAME', 'TRANSACTIONS', 'MRP', 'MARGIN', 'AVG MARGIN', 'SURCHARGE', 'AVG SURCHARGE', 'REFUND MRP', 'TOTAL MRP']);
            $i = 1;
            foreach ($rows as $row) {
                $txn = (int) $row->transactions;
                $mrp = (float) $row->mrp;
                $margin = (float) $row->margin;
                $surcharge = (float) $row->surcharge;
                fputcsv($out, [$i++, $row->api_name, $txn, number_format($mrp, 2, '.', ''), number_format($margin, 2, '.', ''), $txn ? number_format($margin / $txn, 2, '.', '') : '0.00', number_format($surcharge, 2, '.', ''), $txn ? number_format($surcharge / $txn, 2, '.', '') : '0.00', number_format((float) $row->refund_mrp, 2, '.', ''), number_format($mrp, 2, '.', '')]);
            }
            fclose($out);
        }, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""]);
    }
}
