<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierFail2SuccessController extends Controller
{
    public function index()
    {
        \Helper::ensureFailToSuccessTable();
        $apis = DB::table('apis')->orderBy('api_name')->get(['id', 'api_name']);

        return view('admin.recharge-reports.supplier-fail-2-success', compact('apis'));
    }

    public function list(Request $request)
    {
        \Helper::ensureFailToSuccessTable();
        $this->syncDetected($request);

        $limit = (int) ($request->show ?: 10);
        if (! in_array($limit, [10, 25, 50, 100], true)) {
            $limit = 10;
        }
        $page = max(1, (int) ($request->page ?: 1));
        $offset = ($page - 1) * $limit;

        $from = $request->from_date ?: Carbon::today()->subDays(30)->format('Y-m-d');
        $to = $request->to_date ?: Carbon::today()->format('Y-m-d');

        $q = DB::table('supplier_fail_to_success as f')
            ->leftJoin('reports as r', 'r.id', '=', 'f.report_id')
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->leftJoin('providers as p', 'p.id', '=', 'f.provider_id')
            ->leftJoin('apis as la', 'la.id', '=', 'f.last_api_id')
            ->leftJoin('apis as ra', 'ra.id', '=', 'f.response_api_id')
            ->where(function ($w) use ($from, $to) {
                $w->whereBetween('f.response_time', [$from.' 00:00:00', $to.' 23:59:59'])
                    ->orWhereBetween('f.created_at', [$from.' 00:00:00', $to.' 23:59:59']);
            });

        if ($request->api_id) {
            $apiId = (int) $request->api_id;
            $q->where(function ($w) use ($apiId) {
                $w->where('f.last_api_id', $apiId)->orWhere('f.response_api_id', $apiId);
            });
        }
        if ($request->recharge_id) {
            $term = trim((string) $request->recharge_id);
            $q->where(function ($w) use ($term) {
                $w->where('f.recharge_id', 'like', "%{$term}%")
                    ->orWhere('f.number', 'like', "%{$term}%")
                    ->orWhere('r.order_id', 'like', "%{$term}%");
            });
        }

        $total = (clone $q)->count();
        $list = (clone $q)
            ->select(
                'f.*',
                'p.provider_name',
                'la.api_name as last_api',
                'ra.api_name as response_api',
                'u.outlet_name',
                'u.first_name',
                'u.mobile_number as user_mobile',
                'r.status as report_status'
            )
            ->orderByDesc('f.id')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $rows = '';
        if ($list->count() > 0) {
            foreach ($list as $row) {
                $user = trim(($row->outlet_name ?: $row->first_name ?: 'User').' / '.($row->user_mobile ?: '-'));
                $rows .= '<tr>
                    <td><strong>'.e($row->recharge_id ?: '-').'</strong><br><small class="text-muted">#'.e($row->report_id ?: '-').'</small></td>
                    <td>'.e($user).'</td>
                    <td>'.e($row->provider_name ?: '-').'</td>
                    <td>'.e($row->number ?: '-').'</td>
                    <td>₹'.number_format((float) $row->amount, 2).'</td>
                    <td><span class="badge bg-danger">FAILED</span> → <span class="badge bg-success">SUCCESS</span></td>
                    <td>'.e($row->response_api ?: $row->last_api ?: '-').'</td>
                    <td><small>'.e($row->remark ?: '-').'</small></td>
                    <td><small>Recharge: '.e($row->recharge_time ?: '-').'<br>Success: '.e($row->response_time ?: '-').'</small></td>
                </tr>';
            }
        } else {
            $rows = '<tr><td colspan="9" class="text-center text-muted py-4">No fail-to-success records in this date range.</td></tr>';
        }

        return response()->json([
            'type' => 'success',
            'rows' => $rows,
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

    private function syncDetected(Request $request): void
    {
        $from = $request->from_date ?: Carbon::today()->subDays(30)->format('Y-m-d');
        $to = $request->to_date ?: Carbon::today()->format('Y-m-d');

        $rows = DB::table('reports as r')
            ->whereIn('r.transaction_type', ['Recharge', 'Bill Pay', 'Bill Payment'])
            ->where('r.status', 'Success')
            ->where(function ($w) use ($from, $to) {
                $w->whereBetween('r.updated_at', [$from.' 00:00:00', $to.' 23:59:59'])
                    ->orWhereBetween('r.created_at', [$from.' 00:00:00', $to.' 23:59:59']);
            })
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('reports as rf')
                    ->where('rf.transaction_type', 'Refund')
                    ->where(function ($w) {
                        $w->whereColumn('rf.parent__Id', 'r.id')
                            ->orWhereColumn('rf.order_id', 'r.order_id');
                    });
            })
            ->orderByDesc('r.id')
            ->limit(200)
            ->get();

        foreach ($rows as $row) {
            \Helper::recordFailToSuccess($row, 'detected', 'Refund exists then Success', 'Detected: refunded then marked Success');
        }
    }
}
