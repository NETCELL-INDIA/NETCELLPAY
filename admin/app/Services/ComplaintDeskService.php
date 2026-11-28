<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ComplaintDeskService
{
    public const STATUSES = [
        'NEW',
        'UNDER REVIEW',
        'ASSIGNED',
        'IN PROGRESS',
        'WAITING FOR CUSTOMER',
        'RESOLVED',
        'REJECTED',
        'CLOSED',
    ];

    public static $pushSender = null;

    public function schemaReady(): bool
    {
        return Schema::hasTable('complaints')
            && Schema::hasTable('complaint_messages')
            && Schema::hasTable('complaint_status_history')
            && Schema::hasColumn('complaints', 'priority');
    }

    public function workflowStatus(?string $stored): string
    {
        $value = strtoupper(trim((string) $stored));
        $value = str_replace('_', ' ', $value);
        if (in_array($value, ['OPEN', 'PENDING', 'NEW'], true)) {
            return 'NEW';
        }
        if (in_array($value, ['SLOVED', 'SOLVED', 'RESOLVED'], true)) {
            return 'RESOLVED';
        }
        if (in_array($value, ['CLEAR', 'CLEARED', 'CLOSED'], true)) {
            return 'CLOSED';
        }
        if ($value === 'UNDER REVIEW') {
            return 'UNDER REVIEW';
        }
        if (in_array($value, self::STATUSES, true)) {
            return $value;
        }

        return $stored !== null && trim($stored) !== '' ? trim($stored) : 'NEW';
    }

    public function counts(array $filters = []): array
    {
        $base = $this->filteredQuery($filters, false);
        $rows = (clone $base)->select('c.status', DB::raw('count(*) as total'))->groupBy('c.status')->get();
        $counts = [
            'total' => 0,
            'new' => 0,
            'under_review' => 0,
            'assigned' => 0,
            'in_progress' => 0,
            'waiting' => 0,
            'resolved' => 0,
            'rejected' => 0,
            'closed' => 0,
        ];
        foreach ($rows as $row) {
            $n = (int) $row->total;
            $counts['total'] += $n;
            $key = [
                'NEW' => 'new',
                'UNDER REVIEW' => 'under_review',
                'ASSIGNED' => 'assigned',
                'IN PROGRESS' => 'in_progress',
                'WAITING FOR CUSTOMER' => 'waiting',
                'RESOLVED' => 'resolved',
                'REJECTED' => 'rejected',
                'CLOSED' => 'closed',
            ][$this->workflowStatus($row->status)] ?? null;
            if ($key) {
                $counts[$key] += $n;
            }
        }

        return $counts;
    }

    public function page(array $filters)
    {
        return $this->filteredQuery($filters, true)
            ->select($this->listColumns())
            ->orderByDesc('c.id')
            ->paginate(20)
            ->withQueryString();
    }

    public function reportRows(array $filters)
    {
        return $this->filteredQuery($filters, true)
            ->select($this->listColumns())
            ->orderByDesc('c.id')
            ->limit(5000)
            ->get();
    }

    public function find(int $id): ?object
    {
        if ($id < 1 || ! Schema::hasTable('complaints')) {
            return null;
        }

        $query = DB::table('complaints as c')
            ->leftJoin('reports as r', 'r.id', '=', 'c.report_id')
            ->leftJoin('users as u', 'u.id', '=', 'c.user_id')
            ->leftJoin('users as parent', 'parent.id', '=', 'u.parent_id')
            ->leftJoin('services as s', 's.id', '=', 'c.service_id')
            ->leftJoin('providers as p', 'p.id', '=', 'r.provider_id')
            ->leftJoin('apis as a', 'a.id', '=', 'r.api_id')
            ->where('c.id', $id);
        if (Schema::hasColumn('complaints', 'assigned_to')) {
            $query->leftJoin('users as staff', 'staff.id', '=', 'c.assigned_to');
        }

        return $query->first($this->detailColumns());
    }

    public function detailSections(object $complaint): array
    {
        $parentRole = (int) ($complaint->parent_role_id ?? 0);
        $distributor = $parentRole === 5
            ? trim((string) $complaint->parent_id)
            : '';
        $customer = [
            'Customer Name' => $this->personName($complaint->outlet_name, $complaint->first_name, $complaint->last_name),
            'User ID' => $this->showId($complaint->user_id),
            'Retailer ID' => $this->showId($complaint->user_id),
            'Distributor ID' => $distributor !== '' && $distributor !== '0' ? $distributor : 'Not recorded',
            'Mobile Number' => $this->text($complaint->mobile_number),
            'WhatsApp Number' => 'Not recorded',
            'Email' => $this->text($complaint->email_address),
            'Account Status' => $this->accountStatus($complaint->account_status ?? null),
        ];

        $ticket = [
            'Unique Ticket ID' => $this->text($complaint->request_id),
            'Complaint Category' => $this->text($this->col($complaint, 'category') ?: $this->matchedCategory($complaint)),
            'Complaint Reason' => $this->text($complaint->subject),
            'Description' => $this->text($this->col($complaint, 'description') ?: $complaint->subject),
            'Submission Date' => $this->text($complaint->created_at),
            'Priority' => $this->text($this->col($complaint, 'priority') ?: 'normal'),
            'Current Status' => $this->workflowStatus($complaint->status),
            'Assigned Staff' => $this->personName($complaint->staff_outlet, $complaint->staff_first, $complaint->staff_last),
        ];

        $transaction = $this->transactionSection($complaint);
        $specific = $this->specificSection($complaint, $transaction);

        return [
            'customer' => $customer,
            'ticket' => $ticket,
            'transaction' => $transaction,
            'specific' => $specific,
        ];
    }

    public function messages(int $complaintId, bool $includeInternal): array
    {
        if (! Schema::hasTable('complaint_messages')) {
            return [];
        }
        $q = DB::table('complaint_messages')->where('complaint_id', $complaintId);
        if (! $includeInternal) {
            $q->where('visibility', 'customer');
        }

        return $q->orderBy('id')->get()->all();
    }

    public function history(int $complaintId): array
    {
        if (! Schema::hasTable('complaint_status_history')) {
            return [];
        }

        return DB::table('complaint_status_history')->where('complaint_id', $complaintId)->orderBy('id')->get()->all();
    }

    public function attachments(int $complaintId, bool $includeInternal): array
    {
        if (! Schema::hasTable('complaint_attachments')) {
            return [];
        }
        $q = DB::table('complaint_attachments')->where('complaint_id', $complaintId);
        if (! $includeInternal) {
            $q->where('visibility', 'customer');
        }

        return $q->orderBy('id')->get()->all();
    }

    public function feedback(int $complaintId): array
    {
        if (! Schema::hasTable('complaint_feedback')) {
            return [];
        }

        return DB::table('complaint_feedback')->where('complaint_id', $complaintId)->orderBy('id')->get()->all();
    }

    public function staffOptions(): array
    {
        $roles = AdminMenuService::adminRoleIds();

        return DB::table('users')
            ->where('status', 1)
            ->whereIn('role_id', $roles)
            ->orderBy('id')
            ->get(['id', 'first_name', 'last_name', 'outlet_name', 'role_id'])
            ->all();
    }

    public function assign(object $complaint, int $staffId, int $adminId, string $remarks): string
    {
        $staff = DB::table('users')->where('id', $staffId)->where('status', 1)->first(['id', 'role_id']);
        if (! $staff || ! in_array((int) $staff->role_id, AdminMenuService::adminRoleIds(), true)) {
            return 'Choose an active staff account.';
        }
        $now = Carbon::now();
        DB::table('complaint_assignments')->insert([
            'complaint_id' => $complaint->id,
            'staff_id' => $staffId,
            'assigned_by' => $adminId,
            'created_at' => $now,
        ]);
        DB::table('complaints')->where('id', $complaint->id)->update([
            'assigned_to' => $staffId,
            'updated_at' => $now,
        ]);
        $current = $this->workflowStatus($complaint->status);
        if (in_array($current, ['NEW', 'UNDER REVIEW'], true)) {
            $this->writeStatus($complaint, 'ASSIGNED', $adminId, $remarks !== '' ? $remarks : 'Assigned to staff');
        }

        return '';
    }

    public function setPriority(object $complaint, string $priority): string
    {
        if (! in_array($priority, ['low', 'normal', 'high', 'urgent'], true)) {
            return 'Priority must be low, normal, high, or urgent.';
        }
        DB::table('complaints')->where('id', $complaint->id)->update([
            'priority' => $priority,
            'updated_at' => Carbon::now(),
        ]);

        return '';
    }

    public function changeStatus(object $complaint, string $status, int $adminId, string $remarks): string
    {
        $status = $this->workflowStatus($status);
        if (! in_array($status, self::STATUSES, true)) {
            return 'That status is not part of the complaint workflow.';
        }
        if ($status === 'REJECTED' && $remarks === '') {
            return 'A rejection reason is required.';
        }
        if ($status === 'CLOSED' && ! in_array($this->workflowStatus($complaint->status), ['RESOLVED', 'REJECTED', 'CLOSED'], true)) {
            return 'Close a complaint only after it is resolved or rejected.';
        }
        $this->writeStatus($complaint, $status, $adminId, $remarks);

        return '';
    }

    public function addMessage(object $complaint, int $senderId, string $role, string $body, string $visibility, ?UploadedFile $file = null): array
    {
        $body = trim(strip_tags($body));
        if ($body === '' && ! $file) {
            return ['error' => 'Enter a message or choose a file.'];
        }
        if (! in_array($visibility, ['customer', 'internal'], true)) {
            $visibility = 'internal';
        }
        $now = Carbon::now();
        $deliveryStatus = null;
        $deliveryError = null;
        if ($visibility === 'customer' && $role === 'admin') {
            [$deliveryStatus, $deliveryError] = $this->notifyCustomer($complaint, $body !== '' ? $body : 'A file was added to your complaint.');
        }
        $messageId = DB::table('complaint_messages')->insertGetId([
            'complaint_id' => $complaint->id,
            'sender_role' => $role,
            'sender_id' => $senderId,
            'body' => $body !== '' ? $body : 'Attachment',
            'visibility' => $visibility,
            'delivery_status' => $deliveryStatus,
            'delivery_error' => $deliveryError,
            'created_at' => $now,
        ]);
        if ($file) {
            $stored = $this->storeFile($complaint->id, $messageId, $senderId, $file, $visibility);
            if ($stored !== '') {
                return ['error' => $stored, 'id' => $messageId];
            }
        }

        return ['error' => '', 'id' => $messageId, 'delivery_status' => $deliveryStatus];
    }

    public function storeFile(int $complaintId, ?int $messageId, int $uploaderId, UploadedFile $file, string $visibility): string
    {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, $allowed, true) || $file->getSize() > 2 * 1024 * 1024) {
            return 'Upload a JPG, PNG, or PDF up to 2 MB.';
        }
        $mime = (string) $file->getMimeType();
        if (! in_array($mime, ['image/jpeg', 'image/png', 'application/pdf'], true)) {
            return 'Upload a JPG, PNG, or PDF up to 2 MB.';
        }
        $name = Str::uuid()->toString().'.'.$ext;
        $path = $file->storeAs('complaint-attachments/'.$complaintId, $name, 'local');
        if (! is_string($path) || $path === '') {
            return 'The file could not be stored.';
        }
        DB::table('complaint_attachments')->insert([
            'complaint_id' => $complaintId,
            'message_id' => $messageId,
            'stored_path' => $path,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 180),
            'mime' => $mime,
            'size' => (int) $file->getSize(),
            'uploaded_by' => $uploaderId,
            'visibility' => $visibility,
            'created_at' => Carbon::now(),
        ]);

        return '';
    }

    public function categories(): array
    {
        if (! Schema::hasTable('complaint_categories')) {
            return [];
        }

        return DB::table('complaint_categories')->orderBy('label')->get()->all();
    }

    private function writeStatus(object $complaint, string $next, int $adminId, string $remarks): void
    {
        $previous = $this->workflowStatus($complaint->status);
        $now = Carbon::now();
        $update = [
            'status' => $next,
            'updated_at' => $now,
        ];
        if ($remarks !== '') {
            $update['decision_remark'] = mb_substr($remarks, 0, 255);
            $update['decision_by'] = $adminId;
            $update['decision_date'] = $now;
        }
        if ($next === 'RESOLVED') {
            $update['resolved_at'] = $now;
        }
        if ($next === 'CLOSED') {
            $update['closed_at'] = $now;
        }
        if ($next === 'REJECTED') {
            $update['rejected_reason'] = mb_substr($remarks, 0, 255);
        }
        DB::table('complaints')->where('id', $complaint->id)->update($update);
        DB::table('complaint_status_history')->insert([
            'complaint_id' => $complaint->id,
            'previous_status' => $previous,
            'new_status' => $next,
            'admin_id' => $adminId,
            'remarks' => $remarks !== '' ? mb_substr($remarks, 0, 255) : null,
            'created_at' => $now,
        ]);
        $complaint->status = $next;
    }

    private function notifyCustomer(object $complaint, string $body): array
    {
        $userId = (int) ($complaint->user_id ?? 0);
        $user = $userId > 0 ? DB::table('users')->where('id', $userId)->first(['android_fcm_token', 'fcm_token', 'device_token']) : null;
        $token = trim((string) ($user->android_fcm_token ?? $user->fcm_token ?? $user->device_token ?? ''));
        $title = 'Complaint '.$this->text($complaint->request_id);
        $sent = false;
        $error = null;
        if ($token === '') {
            $error = 'no_token';
        } elseif (is_callable(self::$pushSender)) {
            $sent = (bool) (self::$pushSender)($token, $title, $body, $userId);
            $error = $sent ? null : 'push_failed';
        } else {
            $sent = FcmHttpV1Service::send($token, $title, mb_substr($body, 0, 180), [
                'type' => 'complaint',
                'ticket' => (string) ($complaint->request_id ?? ''),
            ], $userId);
            $error = $sent ? null : (FcmHttpV1Service::lastError() ?? 'push_failed');
        }
        $this->inboxCopy($userId, $title, $body, $sent, $error);

        return [$sent ? 'sent' : 'failed', $error];
    }

    private function inboxCopy(int $userId, string $title, string $body, bool $sent, ?string $error): void
    {
        if ($userId < 1 || ! Schema::hasTable('messages')) {
            return;
        }
        try {
            DB::table('messages')->insert([
                'user_id' => $userId,
                'to_user_id' => $userId,
                'subject' => mb_substr($title, 0, 180),
                'content' => mb_substr($body, 0, 1000),
                'msg_source' => 'PUSH',
                'template_id' => '0',
                'status' => $sent ? 1 : 3,
                'delivery_error' => $error,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
        }
    }

    private function filteredQuery(array $filters, bool $joinLabels)
    {
        $q = DB::table('complaints as c');
        if ($joinLabels) {
            $q->leftJoin('users as u', 'u.id', '=', 'c.user_id')
                ->leftJoin('reports as r', 'r.id', '=', 'c.report_id')
                ->leftJoin('services as s', 's.id', '=', 'c.service_id')
                ->leftJoin('providers as p', 'p.id', '=', 'r.provider_id')
                ->leftJoin('apis as a', 'a.id', '=', 'r.api_id');
        } else {
            $q->leftJoin('services as s', 's.id', '=', 'c.service_id');
        }
        $from = trim((string) ($filters['from'] ?? ''));
        $to = trim((string) ($filters['to'] ?? ''));
        if ($from !== '') {
            $q->where('c.created_at', '>=', $from.' 00:00:00');
        }
        if ($to !== '') {
            $q->where('c.created_at', '<=', $to.' 23:59:59');
        }
        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $q->where(function ($inner) use ($category) {
                $inner->where('c.category', $category)->orWhere('c.subject', 'like', '%'.addcslashes($category, '%_\\').'%');
            });
        }
        $queue = (string) ($filters['queue'] ?? '');
        if ($queue === 'mobile') {
            $q->where(function ($inner) {
                $inner->whereNull('s.service_name')->orWhere('s.service_name', 'not like', '%dth%');
            });
        } elseif ($queue === 'dth') {
            $q->where('s.service_name', 'like', '%dth%');
        } elseif ($queue === 'new') {
            $q->whereIn('c.status', ['NEW', 'Open', 'Pending', 'open', 'pending']);
        } elseif ($queue === 'pending') {
            $q->whereIn('c.status', ['NEW', 'Open', 'Pending', 'UNDER REVIEW', 'Under Review', 'open', 'pending']);
        } elseif ($queue === 'assigned') {
            $q->where('c.status', 'ASSIGNED');
        } elseif ($queue === 'waiting') {
            $q->where('c.status', 'WAITING FOR CUSTOMER');
        } elseif ($queue === 'resolved') {
            $q->whereIn('c.status', ['RESOLVED', 'Sloved', 'Solved']);
        } elseif ($queue === 'rejected') {
            $q->where('c.status', 'REJECTED');
        }
        $staff = (int) ($filters['staff'] ?? 0);
        if ($staff > 0 && Schema::hasColumn('complaints', 'assigned_to')) {
            $q->where('c.assigned_to', $staff);
        }

        return $q;
    }

    private function listColumns(): array
    {
        $columns = [
            'c.id', 'c.request_id', 'c.subject', 'c.status', 'c.created_at',
            'c.user_id', 'c.report_id', 'u.outlet_name', 'u.first_name', 'u.mobile_number',
            's.service_name', 'p.provider_name', 'a.api_name', 'r.status as txn_status', 'r.total_amount',
        ];
        foreach (['priority', 'category', 'assigned_to', 'resolved_at'] as $column) {
            if (Schema::hasColumn('complaints', $column)) {
                $columns[] = 'c.'.$column;
            }
        }

        return $columns;
    }

    private function detailColumns(): array
    {
        $columns = [
            'c.*',
            'u.outlet_name', 'u.first_name', 'u.last_name', 'u.mobile_number', 'u.email_address',
            'u.parent_id', 'u.status as account_status',
            'parent.role_id as parent_role_id',
        ];
        if (Schema::hasColumn('complaints', 'assigned_to')) {
            array_push($columns, 'staff.outlet_name as staff_outlet', 'staff.first_name as staff_first', 'staff.last_name as staff_last');
        } else {
            array_push($columns, DB::raw('NULL as staff_outlet'), DB::raw('NULL as staff_first'), DB::raw('NULL as staff_last'));
        }
        array_push(
            $columns,
            's.service_name',
            'p.provider_name',
            'a.api_name',
            'r.order_id as txn_order_id', 'r.number', 'r.total_amount', 'r.amount', 'r.opening_balance',
            'r.closing_balance', 'r.status as txn_status', 'r.transaction_date', 'r.operator_id',
            'r.remark as txn_remark', 'r.transaction_type', 'r.debit_user_id', 'r.credit_user_id',
            'r.api_id', 'r.provider_id', 'r.callback_response', 'r.created_at as txn_created_at',
        );
        foreach (['category', 'description', 'priority', 'intended_number', 'duplicate_report_id', 'assigned_to', 'resolved_at', 'rejected_reason'] as $column) {
            if (! Schema::hasColumn('complaints', $column)) {
                $columns[] = DB::raw('NULL as '.$column);
            }
        }

        return $columns;
    }

    private function transactionSection(object $complaint): array
    {
        if (empty($complaint->report_id)) {
            return ['Transaction ID' => 'Not recorded'];
        }
        $refund = $this->refundRow($complaint);
        $api = $this->apiResponse($complaint);

        return [
            'Transaction ID' => $this->text($complaint->txn_order_id ?: $complaint->order_id),
            'Recharge Number / DTH Customer ID' => $this->text($complaint->number),
            'Operator' => $this->text($complaint->provider_name),
            'Provider / API' => $this->text($complaint->api_name),
            'Recharge Amount' => $complaint->total_amount !== null ? (string) $complaint->total_amount : 'Not recorded',
            'Wallet Amount' => $complaint->amount !== null ? (string) $complaint->amount : 'Not recorded',
            'Transaction Date' => $this->text($complaint->transaction_date ?: $complaint->txn_created_at),
            'Transaction Status' => $this->text($complaint->txn_status),
            'API Response' => $api,
            'Operator Reference ID' => $this->text($complaint->operator_id),
            'Opening Balance' => $complaint->opening_balance !== null ? (string) $complaint->opening_balance : 'Not recorded',
            'Closing Balance' => $complaint->closing_balance !== null ? (string) $complaint->closing_balance : 'Not recorded',
            'Debit User ID' => $this->showId($complaint->debit_user_id),
            'Credit User ID' => $this->showId($complaint->credit_user_id),
            'Transaction Type' => $this->text($complaint->transaction_type),
            'Refund / Reversal' => $refund,
        ];
    }

    private function specificSection(object $complaint, array $transaction): array
    {
        $subject = strtolower((string) ($complaint->subject.' '.$this->col($complaint, 'category').' '.$complaint->service_name));
        $blocks = [];
        if (str_contains($subject, 'fail')) {
            $blocks['Recharge Failed'] = [
                'API Response' => $transaction['API Response'] ?? 'Not recorded',
                'Failure Reason' => $this->text($complaint->txn_remark),
                'Wallet Debit' => ($transaction['Wallet Amount'] ?? 'Not recorded').' / opening '.$transaction['Opening Balance'].' / closing '.$transaction['Closing Balance'],
                'Refund' => $transaction['Refund / Reversal'] ?? 'Not recorded',
            ];
        }
        if (str_contains($subject, 'pending') || strcasecmp((string) $complaint->txn_status, 'Pending') === 0) {
            $started = $complaint->txn_created_at ?: $complaint->transaction_date;
            $duration = 'Not recorded';
            if ($started) {
                $duration = Carbon::parse($started)->diffForHumans(Carbon::now(), true);
            }
            $blocks['Recharge Pending'] = [
                'Current Provider Status' => $this->text($complaint->txn_status),
                'Pending Duration' => $duration,
            ];
        }
        if (str_contains($subject, 'not received') || str_contains($subject, 'success')) {
            $blocks['Success but Not Received'] = [
                'Operator Reference ID' => $this->text($complaint->operator_id),
                'Transaction Response' => $this->text($complaint->callback_response ?: ($transaction['API Response'] ?? null)),
            ];
        }
        if (str_contains($subject, 'wrong')) {
            $blocks['Wrong Number'] = [
                'Original Number' => $this->text($complaint->number),
                'Intended Number' => $this->text($this->col($complaint, 'intended_number')),
            ];
        }
        if (str_contains($subject, 'duplicate')) {
            $other = null;
            if (! empty($this->col($complaint, 'duplicate_report_id'))) {
                $other = DB::table('reports')->where('id', $this->col($complaint, 'duplicate_report_id'))->first(['id', 'order_id', 'number', 'total_amount', 'status', 'transaction_date']);
            }
            $blocks['Duplicate Recharge'] = [
                'This Transaction' => $this->text($complaint->txn_order_id).' / '.$this->text($complaint->txn_status),
                'Other Transaction' => $other
                    ? $other->order_id.' / '.$other->status.' / '.$other->number.' / '.$other->total_amount
                    : 'Not recorded',
            ];
        }
        if (str_contains($subject, 'refund')) {
            $blocks['Refund Not Received'] = [
                'Wallet Ledger' => 'No separate wallet ledger table is present. Report opening '.$this->text($complaint->opening_balance).', closing '.$this->text($complaint->closing_balance).'.',
                'Refund History' => $transaction['Refund / Reversal'] ?? 'Not recorded',
            ];
        }
        if (str_contains(strtolower((string) $complaint->service_name), 'dth') || str_contains($subject, 'dth')) {
            $blocks['DTH Complaint'] = [
                'DTH Operator' => $this->text($complaint->provider_name),
                'Subscriber ID' => $this->text($complaint->number),
                'Plan' => 'Not recorded',
            ];
        }

        return $blocks;
    }

    private function refundRow(object $complaint): string
    {
        $status = (string) ($complaint->txn_status ?? '');
        if (in_array($status, ['Refunded', 'Refund'], true)) {
            return 'This transaction status is '.$status.'.';
        }
        if (empty($complaint->txn_order_id)) {
            return 'Not recorded';
        }
        $other = DB::table('reports')
            ->where('order_id', $complaint->txn_order_id)
            ->where('id', '!=', (int) $complaint->report_id)
            ->whereIn('status', ['Refunded', 'Refund'])
            ->first(['id', 'status', 'amount', 'transaction_date']);
        if (! $other) {
            return 'No refund row recorded for this order.';
        }

        return 'Report '.$other->id.' status '.$other->status.' amount '.$other->amount.' on '.$other->transaction_date.'.';
    }

    private function apiResponse(object $complaint): string
    {
        $orderId = trim((string) ($complaint->txn_order_id ?: $complaint->order_id));
        if ($orderId === '' || ! Schema::hasTable('apilogs')) {
            return 'Not recorded';
        }
        $row = DB::table('apilogs')->where('txnid', $orderId)->orderByDesc('id')->first(['response', 'modal']);
        if (! $row || trim((string) $row->response) === '') {
            return 'Not recorded';
        }

        return trim(($row->modal ? $row->modal.': ' : '').Str::limit(trim((string) $row->response), 1500));
    }

    private function matchedCategory(object $complaint): string
    {
        return '';
    }

    private function personName($outlet, $first, $last): string
    {
        $name = trim((string) $outlet);
        if ($name === '') {
            $name = trim((string) $first.' '.(string) $last);
        }

        return $name !== '' ? $name : 'Not recorded';
    }

    private function col(object $row, string $name)
    {
        return property_exists($row, $name) ? $row->{$name} : null;
    }

    private function text($value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : 'Not recorded';
    }

    private function showId($value): string
    {
        $id = (int) $value;

        return $id > 0 ? (string) $id : 'Not recorded';
    }

    private function accountStatus($status): string
    {
        if ($status === null || $status === '') {
            return 'Not recorded';
        }

        return (int) $status === 1 ? 'Active' : 'Inactive';
    }
}
