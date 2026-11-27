<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminMenuService;
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
        $filters = $this->reportFilters($request);
        $q = $this->filteredMessages($filters);
        $total = (clone $q)->count();
        $hasCampaign = Schema::hasColumn('messages', 'campaign_id');
        $columns = [
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
        ];
        if ($hasCampaign) {
            $columns[] = 'm.campaign_id';
        }
        $rowsData = (clone $q)
            ->orderByDesc('m.id')
            ->offset($offset)
            ->limit($limit)
            ->get($columns);

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
                $isCampaign = $hasCampaign && $row->campaign_id !== null;
                $check = '<input type="checkbox" class="form-check-input notify-check" value="'.(int) $row->id.'" aria-label="Select notification '.(int) $row->id.'">';
                $action = '<button type="button" class="btn btn-primary btn-sm py-0 px-2 btn-edit-notify" data-id="'.(int) $row->id.'">Edit</button> '
                    .'<button type="button" class="btn btn-danger btn-sm py-0 px-2 btn-del-notify" data-id="'.(int) $row->id.'" data-scope="'.($isCampaign ? 'one' : 'send').'">Delete</button>';

                $rows .= '<tr>
                    <td>'.$check.'</td>
                    <td>'.$i.'</td>
                    <td>'.$action.'</td>
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
            $rows = '<tr><td colspan="9" class="text-center text-muted py-3">No notification records found</td></tr>';
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
        if ($status === 4) {
            return '<span class="badge bg-secondary">Consent skipped</span>';
        }

        return '<span class="badge bg-secondary">Inbox saved</span>';
    }

    public function showMessage(Request $request)
    {
        if (! $this->canManageNotification()) {
            return response()->json(['type' => 'error', 'message' => 'You are not allowed to edit this notification.'], 403);
        }
        $row = $this->notificationRow((int) $request->id);
        if (! $row) {
            return response()->json(['type' => 'error', 'message' => 'Notification not found']);
        }

        return response()->json([
            'type' => 'success',
            'id' => (int) $row->id,
            'subject' => (string) $row->subject,
            'content' => (string) $row->content,
            'campaign' => Schema::hasColumn('messages', 'campaign_id') && $row->campaign_id !== null,
        ]);
    }

    public function update(Request $request)
    {
        if (! $this->canManageNotification()) {
            return response()->json(['type' => 'error', 'message' => 'You are not allowed to edit this notification.'], 403);
        }
        $subject = trim(preg_replace('/\s+/', ' ', strip_tags((string) $request->input('subject'))) ?? '');
        $content = trim(strip_tags((string) $request->input('content')));
        if ($subject === '' || mb_strlen($subject) > 120 || $content === '' || mb_strlen($content) > 1000) {
            return response()->json(['type' => 'error', 'message' => 'Title is required (max 120) and message is required (max 1000).']);
        }

        $row = $this->notificationRow((int) $request->id);
        if (! $row) {
            return response()->json(['type' => 'error', 'message' => 'Notification not found']);
        }

        $changes = [
            'subject' => $subject,
            'content' => $content,
            'updated_at' => Carbon::now(),
        ];
        if (Schema::hasColumn('messages', 'campaign_id') && $row->campaign_id !== null) {
            DB::table('messages')->where('id', $row->id)->update($changes);
            $updated = 1;
        } else {
            $from = Carbon::parse($row->created_at)->subMinutes(2);
            $to = Carbon::parse($row->created_at)->addMinutes(2);
            $query = DB::table('messages')
                ->whereIn('msg_source', ['PUSH', 'NOTIFICATION'])
                ->where('subject', $row->subject)
                ->where('content', $row->content)
                ->whereBetween('created_at', [$from, $to]);
            if (Schema::hasColumn('messages', 'campaign_id')) {
                $query->whereNull('campaign_id');
            }
            $updated = $query->update($changes);
        }

        return response()->json([
            'type' => 'success',
            'message' => 'Notification updated ('.$updated.' inbox row'.($updated === 1 ? '' : 's').'). The phone tray alert cannot be changed.',
        ]);
    }

    public function destroyFiltered(Request $request)
    {
        if (! $this->canManageNotification()) {
            return response()->json(['type' => 'error', 'message' => 'You are not allowed to delete these notifications.'], 403);
        }
        if (! Schema::hasTable('messages')) {
            return response()->json(['type' => 'error', 'message' => 'Invalid notification']);
        }

        $filters = $this->reportFilters($request);
        $dayError = $this->dateFilterError($filters['from'], $filters['to']);
        if ($dayError !== null) {
            return response()->json(['type' => 'error', 'message' => $dayError]);
        }
        if ($filters['from'] === '' && $filters['to'] === '' && $filters['user'] === '') {
            return response()->json(['type' => 'error', 'message' => 'Choose a date, a date range, or a user before deleting.']);
        }
        if ($filters['user'] !== '' && mb_strlen($filters['user']) < 4) {
            return response()->json(['type' => 'error', 'message' => 'Enter at least 4 characters of the mobile or user name.']);
        }

        $deleted = 0;
        $this->filteredMessages($filters, false)->select('m.id')->orderBy('m.id')->chunkById(500, function ($rows) use (&$deleted) {
            $deleted += DB::table('messages')->whereIn('id', $rows->pluck('id'))->delete();
        }, 'm.id', 'id');

        return response()->json([
            'type' => 'success',
            'message' => 'Deleted '.$deleted.' inbox notification'.($deleted === 1 ? '' : 's').'. An alert already shown on a phone cannot be recalled.',
        ]);
    }

    public function destroySelected(Request $request)
    {
        if (! $this->canManageNotification()) {
            return response()->json(['type' => 'error', 'message' => 'You are not allowed to delete these notifications.'], 403);
        }
        $raw = $request->input('ids', []);
        if (! is_array($raw)) {
            return response()->json(['type' => 'error', 'message' => 'Choose at least one notification.']);
        }
        $ids = [];
        foreach ($raw as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        $ids = array_values($ids);
        if ($ids === []) {
            return response()->json(['type' => 'error', 'message' => 'Choose at least one notification.']);
        }
        if (count($ids) > 100) {
            return response()->json(['type' => 'error', 'message' => 'Select at most 100 notifications at a time.']);
        }
        if (! Schema::hasTable('messages')) {
            return response()->json(['type' => 'error', 'message' => 'Invalid notification']);
        }

        $deleted = DB::table('messages')
            ->whereIn('id', $ids)
            ->whereIn('msg_source', ['PUSH', 'NOTIFICATION'])
            ->delete();

        return response()->json([
            'type' => 'success',
            'message' => 'Deleted '.$deleted.' selected inbox notification'.($deleted === 1 ? '' : 's').'. An alert already shown on a phone cannot be recalled.',
        ]);
    }

    public function destroy(Request $request)
    {
        if (! $this->canManageNotification()) {
            return response()->json(['type' => 'error', 'message' => 'You are not allowed to delete this notification.'], 403);
        }

        $id = (int) $request->id;
        if ($id <= 0 || ! Schema::hasTable('messages') || ! Schema::hasColumn('messages', 'campaign_id')) {
            return response()->json(['type' => 'error', 'message' => 'Invalid notification']);
        }

        $row = $this->notificationRow($id);
        if (! $row) {
            return response()->json(['type' => 'error', 'message' => 'Notification not found']);
        }

        if (Schema::hasColumn('messages', 'campaign_id') && $row->campaign_id !== null) {
            DB::table('messages')->where('id', $row->id)->delete();

            return response()->json([
                'type' => 'success',
                'message' => 'This inbox notification was deleted. Other recipients and the campaign record were kept. The phone tray alert cannot be recalled.',
            ]);
        }

        $from = Carbon::parse($row->created_at)->subMinutes(2);
        $to = Carbon::parse($row->created_at)->addMinutes(2);

        $deleted = DB::table('messages')
            ->whereIn('msg_source', ['PUSH', 'NOTIFICATION'])
            ->whereNull('campaign_id')
            ->where('subject', $row->subject)
            ->where('content', $row->content)
            ->whereBetween('created_at', [$from, $to])
            ->delete();

        return response()->json([
            'type' => 'success',
            'message' => 'Wrong notification deleted ('.$deleted.' row'.($deleted === 1 ? '' : 's').').',
        ]);
    }

    private function reportFilters(Request $request): array
    {
        $status = trim((string) $request->status);

        return [
            'user' => trim(strip_tags((string) $request->mobile)),
            'status' => $status,
            'from' => trim((string) $request->from_date),
            'to' => trim((string) $request->to_date),
        ];
    }

    private function filteredMessages(array $filters, bool $matchSubject = true)
    {
        $query = DB::table('messages as m')
            ->leftJoin('users as u', 'u.id', '=', 'm.to_user_id');
        $this->applyMessageFilters($query, $filters, $matchSubject);

        return $query;
    }

    private function applyMessageFilters($query, array $filters, bool $matchSubject): void
    {
        $query->whereIn('m.msg_source', ['PUSH', 'NOTIFICATION']);

        $user = $filters['user'];
        if ($user !== '') {
            $like = '%'.addcslashes($user, '%_\\').'%';
            $query->where(function ($inner) use ($like, $matchSubject) {
                $inner->where('u.mobile_number', 'like', $like)
                    ->orWhere('u.outlet_name', 'like', $like)
                    ->orWhere('u.first_name', 'like', $like)
                    ->orWhere('u.last_name', 'like', $like);
                if ($matchSubject) {
                    $inner->orWhere('m.subject', 'like', $like);
                }
            });
        }

        if ($filters['status'] !== '' && $filters['status'] !== 'all' && ctype_digit($filters['status'])) {
            $query->where('m.status', (int) $filters['status']);
        }

        if ($filters['from'] !== '') {
            $query->where('m.created_at', '>=', $filters['from'].' 00:00:00');
        }
        if ($filters['to'] !== '') {
            $query->where('m.created_at', '<=', $filters['to'].' 23:59:59');
        }
    }

    private function dateFilterError(string $from, string $to): ?string
    {
        foreach (['From date' => $from, 'To date' => $to] as $label => $value) {
            if ($value === '') {
                continue;
            }
            $day = Carbon::createFromFormat('Y-m-d', $value);
            if (! $day || $day->format('Y-m-d') !== $value) {
                return $label.' is not a valid date.';
            }
        }
        if ($from !== '' && $to !== '' && $from > $to) {
            return 'From date must be on or before the to date.';
        }

        return null;
    }

    private function notificationRow(int $id): ?object
    {
        if ($id <= 0 || ! Schema::hasTable('messages')) {
            return null;
        }

        return DB::table('messages')
            ->where('id', $id)
            ->whereIn('msg_source', ['PUSH', 'NOTIFICATION'])
            ->first();
    }

    private function canManageNotification(): bool
    {
        $adminId = (int) session('user_id');
        if ($adminId <= 0) {
            return false;
        }
        $admin = DB::table('users')->where('id', $adminId)->first(['role_id', 'status']);
        if (! $admin || (int) $admin->status !== 1 || ! in_array((int) $admin->role_id, AdminMenuService::adminRoleIds(), true)) {
            return false;
        }

        return AdminMenuService::can('extras.notification_report', (int) $admin->role_id);
    }

    private function emptyResponse()
    {
        return response()->json([
            'type' => 'success',
            'rows' => '<tr><td colspan="9" class="text-center text-muted py-3">No notification records found</td></tr>',
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
