<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsumptionReportController extends Controller
{
    public function index()
    {
        $apis = DB::table('apis')->orderBy('api_name')->get(['id', 'api_name']);
        $services = DB::table('services')->orderBy('service_name')->get(['id', 'service_name']);
        $providers = DB::table('providers')->orderBy('provider_name')->get(['id', 'provider_name']);

        return view('admin.recharge-reports.consumption-report', compact('apis', 'services', 'providers'));
    }

    private function base(Request $request)
    {
        $q = DB::table('reports as r')
            ->leftJoin('providers as p', 'p.id', '=', 'r.provider_id')
            ->leftJoin('states as st', 'st.id', '=', 'r.state_id')
            ->whereIn('r.transaction_type', ['Recharge', 'Bill Pay', 'Bill Payment'])
            ->where('r.status', 'Success');

        $from = $request->from_date ?: Carbon::today()->format('Y-m-d');
        $to = $request->to_date ?: Carbon::today()->format('Y-m-d');
        $q->where(function ($w) use ($from, $to) {
            $w->whereBetween('r.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->orWhereBetween('r.transaction_date', [$from . ' 00:00:00', $to . ' 23:59:59']);
        });
        if ($request->api_id) {
            $q->where('r.api_id', (int) $request->api_id);
        }
        if ($request->service_id) {
            $q->where('r.service_id', (int) $request->service_id);
        }
        if ($request->provider_id) {
            $q->where('r.provider_id', (int) $request->provider_id);
        }
        if ($request->user_id) {
            $userId = (int) $request->user_id;
            if ($request->include_child == 1) {
                $ids = array_merge([$userId], DB::table('users')->where('parent_id', $userId)->pluck('id')->toArray());
                $q->whereIn('r.user_id', $ids);
            } else {
                $q->where('r.user_id', $userId);
            }
        }
        return $q;
    }

    public function list(Request $request)
    {
        $limit = in_array((int) $request->show, [10, 25, 50, 100], true) ? (int) $request->show : 10;
        $page = max(1, (int) ($request->page ?: 1));
        $offset = ($page - 1) * $limit;
        $circleWise = $request->circle_wise == 1;

        $select = ['p.provider_name', DB::raw('COUNT(r.id) as txns'), DB::raw('SUM(r.amount) as mrp')];
        $group = ['r.provider_id', 'p.provider_name'];
        if ($circleWise) {
            $select[] = 'st.state_name as circle_name';
            $group[] = 'r.state_id';
            $group[] = 'st.state_name';
        } else {
            $select[] = DB::raw("'ALL' as circle_name");
        }

        $all = $this->base($request)->select($select)->groupBy($group)->orderBy('p.provider_name')->get();
        $total = $all->count();
        $pageRows = $all->slice($offset, $limit)->values();
        $sumTxn = (int) $all->sum('txns');
        $sumMrp = (float) $all->sum('mrp');

        $html = '';
        if ($pageRows->count()) {
            $i = $offset + 1;
            foreach ($pageRows as $row) {
                $html .= '<tr>
                    <td>' . $i++ . '</td>
                    <td>' . e($row->provider_name ?: '-') . '</td>
                    <td>' . e($row->circle_name ?: 'ALL') . '</td>
                    <td>' . (int) $row->txns . '</td>
                    <td>' . number_format((float) $row->mrp, 2) . '</td>
                </tr>';
            }
            $html .= '<tr class="fw-bold table-light"><td></td><td></td><td>Total:</td><td>' . $sumTxn . '</td><td>' . number_format($sumMrp, 2) . '</td></tr>';
        } else {
            $html = '<tr><td colspan="5" class="text-center text-muted py-4">No data available in table</td></tr>
            <tr class="fw-bold table-light"><td></td><td></td><td>Total:</td><td>0.00</td><td>0.00</td></tr>';
        }

        return response()->json([
            'type' => 'success',
            'rows' => $html,
            'pagination' => [
                'page' => $page, 'show' => $limit, 'total' => $total,
                'from' => $total ? $offset + 1 : 0, 'to' => min($offset + $limit, $total),
                'last_page' => max(1, (int) ceil($total / max($limit, 1))),
            ],
        ]);
    }

    public function download(Request $request)
    {
        $request->merge(['show' => 10000, 'page' => 1]);
        $circleWise = $request->circle_wise == 1;
        $select = ['p.provider_name', DB::raw('COUNT(r.id) as txns'), DB::raw('SUM(r.amount) as mrp')];
        $group = ['r.provider_id', 'p.provider_name'];
        if ($circleWise) {
            $select[] = 'st.state_name as circle_name';
            $group[] = 'r.state_id';
            $group[] = 'st.state_name';
        } else {
            $select[] = DB::raw("'ALL' as circle_name");
        }
        $rows = $this->base($request)->select($select)->groupBy($group)->orderBy('p.provider_name')->get();
        $filename = 'consumption-report-' . date('Ymd-His') . '.csv';
        return response()->stream(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SR NO', 'OPERATOR', 'CIRCLE', 'TXNS', 'MRP']);
            $i = 1;
            foreach ($rows as $row) {
                fputcsv($out, [$i++, $row->provider_name, $row->circle_name, $row->txns, $row->mrp]);
            }
            fclose($out);
        }, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""]);
    }
}
