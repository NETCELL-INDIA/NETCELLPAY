<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAudit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditLogController extends Controller
{
    public function index()
    {
        AdminAudit::ensureTable();

        return view('admin.system.audit-log');
    }

    public function list(Request $request)
    {
        AdminAudit::ensureTable();
        if (! Schema::hasTable('admin_audit_logs')) {
            return response()->json([
                'type' => 'success',
                'rows' => '<tr><td colspan="8" class="text-center text-muted">No audit table</td></tr>',
                'pagination' => ['page' => 1, 'show' => 10, 'total' => 0, 'from' => 0, 'to' => 0, 'last_page' => 1],
            ]);
        }
        $from = $request->from_date ?: Carbon::today()->format('Y-m-d');
        $to = $request->to_date ?: $from;
        $limit = in_array((int) $request->show, [10, 25, 50], true) ? (int) $request->show : 25;
        $page = max(1, (int) ($request->page ?: 1));
        $offset = ($page - 1) * $limit;

        $q = DB::table('admin_audit_logs')->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);
        if ($request->module && $request->module !== 'All') {
            $q->where('module', $request->module);
        }
        if ($request->q) {
            $term = trim((string) $request->q);
            $q->where(function ($w) use ($term) {
                $w->where('action', 'like', "%{$term}%")
                    ->orWhere('admin_name', 'like', "%{$term}%")
                    ->orWhere('ref_id', 'like', "%{$term}%")
                    ->orWhere('remark', 'like', "%{$term}%");
            });
        }
        $total = (clone $q)->count();
        $rows = (clone $q)->orderByDesc('id')->offset($offset)->limit($limit)->get();
        $html = '';
        if ($rows->count()) {
            foreach ($rows as $r) {
                $html .= '<tr>
                    <td>'.e($r->created_at).'</td>
                    <td>'.e($r->admin_name).'</td>
                    <td><span class="badge bg-secondary">'.e($r->module).'</span></td>
                    <td>'.e($r->action).'</td>
                    <td>'.e(($r->ref_type ?: '').' #'.($r->ref_id ?: '')).'</td>
                    <td class="small">'.e(\Illuminate\Support\Str::limit((string) $r->old_value, 80)).'</td>
                    <td class="small">'.e(\Illuminate\Support\Str::limit((string) $r->new_value, 80)).'</td>
                    <td>'.e($r->remark ?: '-').'</td>
                </tr>';
            }
        } else {
            $html = '<tr><td colspan="8" class="text-center text-muted py-4">No audit rows</td></tr>';
        }

        return response()->json([
            'type' => 'success',
            'rows' => $html,
            'pagination' => [
                'page' => $page,
                'show' => $limit,
                'total' => $total,
                'from' => $total ? $offset + 1 : 0,
                'to' => min($offset + $limit, $total),
                'last_page' => max(1, (int) ceil($total / max($limit, 1))),
            ],
        ]);
    }
}
