<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NotificationSendReportController extends Controller
{
    public function index()
    {
        return view('admin.extras.notification-send-report');
    }

    public function list(Request $request)
    {
        if (! Schema::hasTable('messages')) {
            return $this->emptyResponse();
        }

        $limit = (int) ($request->show ?: 10);
        if (! in_array($limit, [10, 25, 50, 100], true)) {
            $limit = 10;
        }

        $page = max(1, (int) ($request->page ?: 1));
        $offset = ($page - 1) * $limit;
        $mobile = trim((string) $request->mobile);
        $status = trim((string) $request->status);
        $from = trim((string) $request->from_date);
        $to = trim((string) $request->to_date);

        $q = DB::table('messages as m')
            ->leftJoin('users as u', 'u.id', '=', 'm.to_user_id')
            ->whereIn('m.msg_source', ['PUSH', 'NOTIFICATION']);

        if ($mobile !== '') {
            $q->where(function ($w) use ($mobile) {
                $w->where('u.mobile_number', 'like', '%'.$mobile.'%')
                    ->orWhere('u.outlet_name', 'like', '%'.$mobile.'%')
                    ->orWhere('m.subject', 'like', '%'.$mobile.'%');
            });
        }

        if ($status !== '' && $status !== 'all') {
            $q->where('m.status', (int) $status);
        }

        if ($from !== '') {
            $q->where('m.created_at', '>=', $from.' 00:00:00');
        }
        if ($to !== '') {
            $q->where('m.created_at', '<=', $to.' 23:59:59');
        }

        $total = (clone $q)->count();
        $rowsData = (clone $q)
            ->orderByDesc('m.id')
            ->offset($offset)
            ->limit($limit)
            ->get([
                'm.id',
                'm.subject',
                'm.content',
                'm.status',
                'm.created_at',
                'u.first_name',
                'u.middle_name',
                'u.last_name',
                'u.outlet_name',
                'u.mobile_number',
            ]);

        $rows = '';
        if ($rowsData->count() > 0) {
            $i = $offset + 1;
            foreach ($rowsData as $row) {
                $name = trim(($row->first_name ?? '').' '.($row->middle_name ?? '').' '.($row->last_name ?? ''));
                if ($name === '') {
                    $name = $row->outlet_name ?: '-';
                }
                $created = $row->created_at
                    ? Carbon::parse($row->created_at)->format('d-m-Y h:i:s a')
                    : '-';
                $badge = $this->statusBadge((int) $row->status);

                $rows .= '<tr>
                    <td>'.$i.'</td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm py-0 px-2 btn-del-notify" data-id="'.(int) $row->id.'">Delete</button>
                    </td>
                    <td>'.e($name).'<br><small>'.e($row->outlet_name ?: '-').'</small></td>
                    <td>'.e($row->mobile_number ?: '-').'</td>
                    <td>'.e($row->subject ?: '-').'</td>
                    <td class="sms-message" title="'.e((string) $row->content).'">'.e(Str::limit(strip_tags((string) $row->content), 80)).'</td>
                    <td>'.$badge.'</td>
                    <td class="sms-date">'.e($created).'</td>
                </tr>';
                $i++;
            }
        } else {
            $rows = '<tr><td colspan="8" class="text-center text-muted py-3">No notification records found</td></tr>';
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

    private function statusBadge(int $status): string
    {
        if ($status === 1) {
            return '<span class="badge bg-success">Push sent</span>';
        }
        if ($status === 2) {
            return '<span class="badge bg-warning text-dark">No app token</span>';
        }
        if ($status === 3) {
            return '<span class="badge bg-danger">Failed</span>';
        }

        return '<span class="badge bg-secondary">Inbox saved</span>';
    }

    public function destroy(Request $request)
    {
        $id = (int) $request->id;
        if ($id <= 0 || ! Schema::hasTable('messages')) {
            return response()->json(['type' => 'error', 'message' => 'Invalid notification']);
        }

        $row = DB::table('messages')
            ->where('id', $id)
            ->whereIn('msg_source', ['PUSH', 'NOTIFICATION'])
            ->first();

        if (! $row) {
            return response()->json(['type' => 'error', 'message' => 'Notification not found']);
        }

        $from = Carbon::parse($row->created_at)->subMinutes(2);
        $to = Carbon::parse($row->created_at)->addMinutes(2);

        $deleted = DB::table('messages')
            ->whereIn('msg_source', ['PUSH', 'NOTIFICATION'])
            ->where('subject', $row->subject)
            ->where('content', $row->content)
            ->whereBetween('created_at', [$from, $to])
            ->delete();

        return response()->json([
            'type' => 'success',
            'message' => 'Wrong notification deleted ('.$deleted.' row'.($deleted === 1 ? '' : 's').').',
        ]);
    }

    private function emptyResponse()
    {
        return response()->json([
            'type' => 'success',
            'rows' => '<tr><td colspan="8" class="text-center text-muted py-3">No notification records found</td></tr>',
            'pagination' => [
                'page' => 1,
                'show' => 10,
                'total' => 0,
                'from' => 0,
                'to' => 0,
                'last_page' => 1,
            ],
        ]);
    }
}
