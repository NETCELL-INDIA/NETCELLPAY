<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RechargeLogsController extends Controller
{
    public function index()
    {
        $apis = DB::table('apis')->orderBy('api_name')->get(['id', 'api_name']);
        $defaultDate = Carbon::today()->format('Y-m-d');
        if (Schema::hasTable('apilogs')) {
            $last = DB::table('apilogs')->orderByDesc('id')->value('created_at');
            if ($last) {
                $defaultDate = Carbon::parse($last)->format('Y-m-d');
            }
        }

        return view('admin.recharge-reports.recharge-logs', compact('apis', 'defaultDate'));
    }

    public function list(Request $request)
    {
        if (!Schema::hasTable('apilogs')) {
            return response()->json([
                'type' => 'success',
                'rows' => '<tr><td colspan="6" class="text-center text-muted py-4">No data available in table</td></tr>',
                'pagination' => ['page' => 1, 'show' => 10, 'total' => 0, 'from' => 0, 'to' => 0, 'last_page' => 1],
            ]);
        }

        $limit = (int) ($request->show ?: 10);
        if (!in_array($limit, [10, 25, 50, 100], true)) {
            $limit = 10;
        }
        $page = max(1, (int) ($request->page ?: 1));
        $offset = ($page - 1) * $limit;
        $date = $request->from_date ?: Carbon::today()->format('Y-m-d');

        $q = DB::table('apilogs as l')
            ->leftJoin(DB::raw('reports as r'), function ($join) {
                $join->whereRaw('r.order_id COLLATE utf8mb4_unicode_ci = l.txnid COLLATE utf8mb4_unicode_ci');
            })
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->leftJoin('apis as a', 'a.id', '=', 'r.api_id')
            ->whereDate('l.created_at', $date);

        if ($request->user_id) {
            $q->where('r.user_id', (int) $request->user_id);
        }
        if ($request->api_id) {
            $q->where('r.api_id', (int) $request->api_id);
        }
        if ($request->type && $request->type !== 'All') {
            $q->where('l.modal', 'like', '%' . $request->type . '%');
        }
        if ($request->recharge_id) {
            $term = trim($request->recharge_id);
            $q->where(function ($w) use ($term) {
                $w->where('l.txnid', 'like', "%{$term}%")
                    ->orWhere('r.order_id', 'like', "%{$term}%");
            });
        }
        if ($request->client_number) {
            $term = trim($request->client_number);
            $q->where(function ($w) use ($term) {
                $w->where('r.number', 'like', "%{$term}%")
                    ->orWhere('r.request_order_id', 'like', "%{$term}%")
                    ->orWhere('l.txnid', 'like', "%{$term}%");
            });
        }

        $total = (clone $q)->count();
        $logs = (clone $q)
            ->select('l.*', 'r.number', 'r.request_order_id', 'u.outlet_name', 'a.api_name')
            ->orderByDesc('l.id')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $rows = '';
        if ($logs->count() > 0) {
            foreach ($logs as $log) {
                $req = $log->request ?: $log->url;
                $resp = $log->response;
                $reqShort = e(\Illuminate\Support\Str::limit((string) $req, 120));
                $respShort = e(\Illuminate\Support\Str::limit((string) $resp, 120));
                $created = $log->created_at;
                $updated = $log->updated_at ?: $log->created_at;
                $diff = '-';
                try {
                    $diffSec = Carbon::parse($created)->diffInSeconds(Carbon::parse($updated));
                    $diff = $diffSec . 's';
                } catch (\Throwable $e) {
                }

                $rows .= '<tr>
                    <td>' . e($log->txnid ?: '-') . '</td>
                    <td>' . e($log->request_order_id ?: ($log->number ?: '-')) . '</td>
                    <td>' . e($log->modal ?: '-') . '</td>
                    <td><div><b>REQ:</b> <small>' . $reqShort . '</small></div><div class="mt-1"><b>RES:</b> <small>' . $respShort . '</small></div>
                        <button type="button" class="btn btn-sm btn-link p-0 btn-view-log" data-req="' . e($req) . '" data-res="' . e($resp) . '">View full</button></td>
                    <td><small>' . e($created) . '<br>' . e($updated) . '</small></td>
                    <td>' . e($diff) . '</td>
                </tr>';
            }
        } else {
            $rows = '<tr><td colspan="6" class="text-center text-muted py-4">No data available in table</td></tr>';
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
}
