<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PlanLogsReportController extends Controller
{
    public function index()
    {
        $defaultDate = Carbon::today()->format('Y-m-d');
        if (Schema::hasTable('apilogs')) {
            $last = DB::table('apilogs')
                ->whereIn('modal', ['Plans', 'Roffer', 'CHECK_MOBILE', 'DTH INFO', 'HLR'])
                ->orderByDesc('id')->value('created_at');
            if (!$last) {
                $last = DB::table('apilogs')->orderByDesc('id')->value('created_at');
            }
            if ($last) {
                $defaultDate = Carbon::parse($last)->format('Y-m-d');
            }
        }

        return view('admin.recharge-reports.plan-logs-report', compact('defaultDate'));
    }

    public function list(Request $request)
    {
        if (!Schema::hasTable('apilogs')) {
            return response()->json([
                'type' => 'success',
                'rows' => '<tr><td colspan="5" class="text-center text-muted py-4">No data available in table</td></tr>',
                'pagination' => ['page' => 1, 'show' => 10, 'total' => 0, 'from' => 0, 'to' => 0, 'last_page' => 1],
            ]);
        }

        $limit = in_array((int) $request->show, [10, 25, 50, 100], true) ? (int) $request->show : 10;
        $page = max(1, (int) ($request->page ?: 1));
        $offset = ($page - 1) * $limit;
        $date = $request->from_date ?: Carbon::today()->format('Y-m-d');

        $q = DB::table('apilogs')->whereDate('created_at', $date)
            ->whereIn('modal', ['Plans', 'Roffer', 'CHECK_MOBILE', 'DTH INFO', 'HLR', 'Plan', 'ROFFER']);

        if ($request->type && $request->type !== 'All') {
            $q->where('modal', 'like', '%' . $request->type . '%');
        }
        if ($request->number) {
            $term = trim($request->number);
            $q->where(function ($w) use ($term) {
                $w->where('txnid', 'like', "%{$term}%")
                    ->orWhere('url', 'like', "%{$term}%")
                    ->orWhere('request', 'like', "%{$term}%")
                    ->orWhere('response', 'like', "%{$term}%");
            });
        }

        // If no plan-type rows for date, fall back to all logs for that date when type=All
        $total = (clone $q)->count();
        if ($total === 0 && (!$request->type || $request->type === 'All') && !$request->number) {
            $q = DB::table('apilogs')->whereDate('created_at', $date);
            $total = (clone $q)->count();
        }

        $logs = (clone $q)->orderByDesc('id')->offset($offset)->limit($limit)->get();
        $html = '';
        if ($logs->count()) {
            foreach ($logs as $log) {
                $req = $log->request ?: $log->url;
                $resp = $log->response;
                $created = $log->created_at;
                $updated = $log->updated_at ?: $log->created_at;
                $diff = '-';
                try {
                    $diff = Carbon::parse($created)->diffInSeconds(Carbon::parse($updated)) . 's';
                } catch (\Throwable $e) {
                }
                $html .= '<tr>
                    <td>' . e($log->txnid ?: '-') . '</td>
                    <td>' . e($log->modal ?: '-') . '</td>
                    <td><div><b>REQ:</b> <small>' . e(Str::limit((string) $req, 100)) . '</small></div>
                        <div><b>RES:</b> <small>' . e(Str::limit((string) $resp, 100)) . '</small></div>
                        <button type="button" class="btn btn-sm btn-link p-0 btn-view-plan-log" data-req="' . e($req) . '" data-res="' . e($resp) . '">View full</button></td>
                    <td><small>' . e($created) . '<br>' . e($updated) . '</small></td>
                    <td>' . e($diff) . '</td>
                </tr>';
            }
        } else {
            $html = '<tr><td colspan="5" class="text-center text-muted py-4">No data available in table</td></tr>';
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
}
