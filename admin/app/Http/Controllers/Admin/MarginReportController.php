<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarginReportController extends Controller
{
    public function index()
    {
        $providers = DB::table('providers')->orderBy('provider_name')->get(['id', 'provider_name']);

        return view('admin.recharge-reports.margin-report', compact('providers'));
    }

    private function baseQuery(Request $request)
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

        if ($request->provider_id) {
            $q->where('r.provider_id', (int) $request->provider_id);
        }

        if ($request->user_id) {
            $userId = (int) $request->user_id;
            $childIds = DB::table('users')->where('parent_id', $userId)->pluck('id')->toArray();
            $ids = array_merge([$userId], $childIds);
            $q->whereIn('r.user_id', $ids);
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
        $circleWise = $request->circle_wise == 1 || $request->circle_wise === '1' || $request->circle_wise === true;

        $base = $this->baseQuery($request);

        $select = [
            'p.provider_name',
            DB::raw('COUNT(r.id) as txns'),
            DB::raw('SUM(r.amount) as mrp'),
            DB::raw('SUM(COALESCE(r.commission,0)) as margin'),
            DB::raw('SUM(GREATEST(COALESCE(r.total_amount,0) - COALESCE(r.amount,0), 0)) as surcharge'),
            DB::raw('0 as child_margin'),
            DB::raw('0 as bonus'),
            DB::raw('0 as roffer'),
        ];

        if ($circleWise) {
            $select[] = 'st.state_name as circle_name';
            $grouped = (clone $base)
                ->select($select)
                ->groupBy('r.provider_id', 'p.provider_name', 'r.state_id', 'st.state_name')
                ->orderBy('p.provider_name');
        } else {
            $select[] = DB::raw("'ALL' as circle_name");
            $grouped = (clone $base)
                ->select($select)
                ->groupBy('r.provider_id', 'p.provider_name')
                ->orderBy('p.provider_name');
        }

        // Child margin when a parent user is selected
        if ($request->user_id) {
            $userId = (int) $request->user_id;
            $childIds = DB::table('users')->where('parent_id', $userId)->pluck('id')->toArray();
            if (!empty($childIds)) {
                // recalculate with child_margin as commission of child users only
                // Applied in PHP after fetch for simplicity on current page totals
            }
        }

        $allRows = $grouped->get();

        // Attach child margin per group if user filter set
        if ($request->user_id) {
            $userId = (int) $request->user_id;
            $childIds = DB::table('users')->where('parent_id', $userId)->pluck('id')->toArray();
            if (!empty($childIds)) {
                foreach ($allRows as $row) {
                    $cq = DB::table('reports as r')
                        ->whereIn('r.transaction_type', ['Recharge', 'Bill Pay', 'Bill Payment'])
                        ->where('r.status', 'Success')
                        ->whereIn('r.user_id', $childIds);
                    $from = $request->from_date ?: Carbon::today()->format('Y-m-d');
                    $to = $request->to_date ?: Carbon::today()->format('Y-m-d');
                    $cq->where(function ($w) use ($from, $to) {
                        $w->whereBetween('r.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                            ->orWhereBetween('r.transaction_date', [$from . ' 00:00:00', $to . ' 23:59:59']);
                    });
                    if ($request->provider_id) {
                        $cq->where('r.provider_id', (int) $request->provider_id);
                    }
                    // Match provider name grouping
                    $provider = DB::table('providers')->where('provider_name', $row->provider_name)->value('id');
                    if ($provider) {
                        $cq->where('r.provider_id', $provider);
                    }
                    if ($circleWise && $row->circle_name && $row->circle_name !== 'ALL') {
                        $stateId = DB::table('states')->where('state_name', $row->circle_name)->value('id');
                        if ($stateId) {
                            $cq->where('r.state_id', $stateId);
                        }
                    }
                    $row->child_margin = (float) $cq->sum('commission');
                }
            }
        }

        $total = $allRows->count();
        $pageRows = $allRows->slice($offset, $limit)->values();

        $sumMrp = (float) $allRows->sum('mrp');
        $sumMargin = (float) $allRows->sum('margin');
        $sumChild = (float) $allRows->sum('child_margin');
        $sumSurcharge = (float) $allRows->sum('surcharge');
        $sumBonus = (float) $allRows->sum('bonus');
        $sumRoffer = (float) $allRows->sum('roffer');
        $sumTxns = (int) $allRows->sum('txns');

        $html = '';
        if ($pageRows->count() > 0) {
            $i = $offset + 1;
            foreach ($pageRows as $row) {
                $html .= '<tr>
                    <td>' . $i++ . '</td>
                    <td>' . e($row->provider_name ?: '-') . '</td>
                    <td>' . e($row->circle_name ?: 'ALL') . '</td>
                    <td>' . (int) $row->txns . '</td>
                    <td>' . number_format((float) $row->mrp, 2) . '</td>
                    <td>' . number_format((float) $row->margin, 2) . '</td>
                    <td>' . number_format((float) $row->child_margin, 2) . '</td>
                    <td>' . number_format((float) $row->surcharge, 2) . '</td>
                    <td>' . number_format((float) $row->bonus, 2) . '</td>
                    <td>' . number_format((float) $row->roffer, 2) . '</td>
                </tr>';
            }
            $html .= '<tr class="fw-bold table-light">
                <td colspan="2"></td>
                <td>Total:</td>
                <td>' . $sumTxns . '</td>
                <td>' . number_format($sumMrp, 2) . '</td>
                <td>' . number_format($sumMargin, 2) . '</td>
                <td>' . number_format($sumChild, 2) . '</td>
                <td>' . number_format($sumSurcharge, 2) . '</td>
                <td>' . number_format($sumBonus, 2) . '</td>
                <td>' . number_format($sumRoffer, 2) . '</td>
            </tr>';
        } else {
            $html = '<tr><td colspan="10" class="text-center text-muted py-4">No data available in table</td></tr>';
        }

        return response()->json([
            'type' => 'success',
            'rows' => $html,
            'summary' => [
                'mrp' => number_format($sumMrp, 2),
                'margin' => number_format($sumMargin, 2),
                'child_margin' => number_format($sumChild, 2),
                'surcharge' => number_format($sumSurcharge, 2),
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
        $request->merge(['show' => 10000, 'page' => 1]);
        $circleWise = $request->circle_wise == 1 || $request->circle_wise === '1';

        $base = $this->baseQuery($request);
        $select = [
            'p.provider_name',
            DB::raw('COUNT(r.id) as txns'),
            DB::raw('SUM(r.amount) as mrp'),
            DB::raw('SUM(COALESCE(r.commission,0)) as margin'),
            DB::raw('SUM(GREATEST(COALESCE(r.total_amount,0) - COALESCE(r.amount,0), 0)) as surcharge'),
        ];

        if ($circleWise) {
            $select[] = 'st.state_name as circle_name';
            $rows = (clone $base)->select($select)
                ->groupBy('r.provider_id', 'p.provider_name', 'r.state_id', 'st.state_name')
                ->orderBy('p.provider_name')->get();
        } else {
            $select[] = DB::raw("'ALL' as circle_name");
            $rows = (clone $base)->select($select)
                ->groupBy('r.provider_id', 'p.provider_name')
                ->orderBy('p.provider_name')->get();
        }

        $filename = 'margin-report-' . date('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SR NO', 'OPERATOR', 'CIRCLE', 'TXNS', 'MRP', 'MARGIN', 'CHILD MARGIN', 'SURCHARGE', 'BONUS', 'R-OFFER']);
            $i = 1;
            foreach ($rows as $row) {
                fputcsv($out, [
                    $i++,
                    $row->provider_name,
                    $row->circle_name ?: 'ALL',
                    (int) $row->txns,
                    number_format((float) $row->mrp, 2, '.', ''),
                    number_format((float) $row->margin, 2, '.', ''),
                    '0.00',
                    number_format((float) $row->surcharge, 2, '.', ''),
                    '0.00',
                    '0.00',
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
