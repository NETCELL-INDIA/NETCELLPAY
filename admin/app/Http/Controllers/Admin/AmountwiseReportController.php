<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AmountwiseReportController extends Controller
{
    public function index()
    {
        $apis = DB::table('apis')->orderBy('api_name')->get(['id', 'api_name']);
        $services = DB::table('services')->orderBy('service_name')->get(['id', 'service_name']);
        $providers = DB::table('providers')->orderBy('provider_name')->get(['id', 'provider_name']);
        $circles = DB::table('states')->orderBy('state_name')->get(['id', 'state_name']);

        return view('admin.recharge-reports.amountwise-report', compact('apis', 'services', 'providers', 'circles'));
    }

    private function base(Request $request)
    {
        $q = DB::table('reports as r')
            ->leftJoin('providers as p', 'p.id', '=', 'r.provider_id')
            ->leftJoin('states as st', 'st.id', '=', 'r.state_id')
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
        if ($request->user_id) {
            $q->where('r.user_id', (int) $request->user_id);
        }
        if ($request->service_id) {
            $q->where('r.service_id', (int) $request->service_id);
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
        return $q;
    }

    public function list(Request $request)
    {
        $byCircle = $request->view_circle == 1;
        $byApi = $request->view_api == 1;

        $select = [
            'p.provider_name',
            'r.status',
            'r.amount',
            DB::raw('COUNT(r.id) as no_of_txns'),
            DB::raw('SUM(r.amount) as total_mrp'),
        ];
        $group = ['r.provider_id', 'p.provider_name', 'r.status', 'r.amount'];

        if ($byCircle) {
            $select[] = 'st.state_name as circle_name';
            $group[] = 'r.state_id';
            $group[] = 'st.state_name';
        } else {
            $select[] = DB::raw("'ALL' as circle_name");
        }
        if ($byApi) {
            $select[] = 'a.api_name';
            $group[] = 'r.api_id';
            $group[] = 'a.api_name';
        } else {
            $select[] = DB::raw("'ALL' as api_name");
        }

        $rows = $this->base($request)->select($select)->groupBy($group)->orderBy('p.provider_name')->orderBy('r.amount')->get();
        $grand = (float) $rows->sum('total_mrp');
        $sumTxn = (int) $rows->sum('no_of_txns');

        $html = '';
        if ($rows->count()) {
            $i = 1;
            foreach ($rows as $row) {
                $pct = $grand > 0 ? ((float) $row->total_mrp / $grand) * 100 : 0;
                $html .= '<tr>
                    <td>' . $i++ . '</td>
                    <td>' . e($row->provider_name ?: '-') . '</td>
                    <td>' . e($row->circle_name ?: 'ALL') . '</td>
                    <td>' . e($row->api_name ?: 'ALL') . '</td>
                    <td>' . e($row->status ?: '-') . '</td>
                    <td>' . number_format((float) $row->amount, 2) . '</td>
                    <td>' . (int) $row->no_of_txns . '</td>
                    <td>' . number_format((float) $row->total_mrp, 2) . '</td>
                    <td>' . number_format($pct, 2) . '%</td>
                </tr>';
            }
            $html .= '<tr class="fw-bold table-light"><td colspan="5"></td><td>Total:</td><td>' . $sumTxn . '</td><td>' . number_format($grand, 2) . '</td><td></td></tr>';
        } else {
            $html = '<tr><td colspan="9" class="text-center text-muted py-4">No data available in table</td></tr>
            <tr class="fw-bold table-light"><td colspan="5"></td><td>Total:</td><td>0.00</td><td>0.00</td><td></td></tr>';
        }

        return response()->json(['type' => 'success', 'rows' => $html]);
    }

    public function download(Request $request)
    {
        $request->merge(['view_circle' => $request->view_circle, 'view_api' => $request->view_api]);
        $res = json_decode($this->list($request)->getContent(), true);
        // rebuild simple CSV via same grouping
        $byCircle = $request->view_circle == 1;
        $byApi = $request->view_api == 1;
        $select = ['p.provider_name', 'r.status', 'r.amount', DB::raw('COUNT(r.id) as no_of_txns'), DB::raw('SUM(r.amount) as total_mrp')];
        $group = ['r.provider_id', 'p.provider_name', 'r.status', 'r.amount'];
        if ($byCircle) {
            $select[] = 'st.state_name as circle_name';
            $group[] = 'r.state_id';
            $group[] = 'st.state_name';
        } else {
            $select[] = DB::raw("'ALL' as circle_name");
        }
        if ($byApi) {
            $select[] = 'a.api_name';
            $group[] = 'r.api_id';
            $group[] = 'a.api_name';
        } else {
            $select[] = DB::raw("'ALL' as api_name");
        }
        $rows = $this->base($request)->select($select)->groupBy($group)->orderBy('p.provider_name')->get();
        $grand = (float) $rows->sum('total_mrp');
        $filename = 'amountwise-report-' . date('Ymd-His') . '.csv';
        return response()->stream(function () use ($rows, $grand) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SR NO', 'OPERATOR', 'CIRCLE', 'API', 'STATUS', 'AMOUNT', 'NO OF TXNS', 'TOTAL MRP', 'PERCENTAGE']);
            $i = 1;
            foreach ($rows as $row) {
                $pct = $grand > 0 ? ((float) $row->total_mrp / $grand) * 100 : 0;
                fputcsv($out, [$i++, $row->provider_name, $row->circle_name, $row->api_name, $row->status, $row->amount, $row->no_of_txns, $row->total_mrp, number_format($pct, 2)]);
            }
            fclose($out);
        }, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""]);
    }
}
