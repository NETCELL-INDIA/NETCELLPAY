<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAudit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PendingReportController extends Controller
{
    public function index()
    {
        $apis = $this->rechargeApis();
        $providers = DB::table('providers')->orderBy('provider_name')->get(['id', 'provider_name']);

        return view('admin.recharge-reports.pending-report', compact('apis', 'providers'));
    }

    private function rechargeApis()
    {
        $q = DB::table('apis')
            ->where('deleted_at', '!=', 1)
            ->where('status', 1)
            ->orderBy('api_name');

        if (Schema::hasColumn('apis', 'api_type')) {
            $q->where(function ($w) {
                $w->whereNull('api_type')
                    ->orWhere('api_type', '')
                    ->orWhere('api_type', 'recharge');
            });
        }

        return $q->get(['id', 'api_name']);
    }

    private function baseQuery(Request $request)
    {
        $q = DB::table('reports as r')
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->leftJoin('providers as p', 'p.id', '=', 'r.provider_id')
            ->leftJoin('apis as a', 'a.id', '=', 'r.api_id')
            ->whereIn('r.transaction_type', ['Recharge', 'Bill Pay', 'Bill Payment'])
            ->whereIn('r.status', ['Pending', 'Under Proces', 'Under Process', 'Processing']);

        // Default: all pending (any day). Date filter only when user selects dates.
        $from = trim((string) ($request->from_date ?? ''));
        $to = trim((string) ($request->to_date ?? ''));
        if ($from !== '' || $to !== '') {
            if ($from === '') {
                $from = '1970-01-01';
            }
            if ($to === '') {
                $to = Carbon::today()->format('Y-m-d');
            }
            $q->where(function ($w) use ($from, $to) {
                $w->whereBetween('r.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                    ->orWhereBetween('r.transaction_date', [$from . ' 00:00:00', $to . ' 23:59:59']);
            });
        }

        if ($request->api_id) {
            $q->where('r.api_id', (int) $request->api_id);
        }
        if ($request->provider_id) {
            $q->where('r.provider_id', (int) $request->provider_id);
        }
        if ($request->user_id) {
            $q->where('r.user_id', (int) $request->user_id);
        }
        if ($request->search_text) {
            $term = trim($request->search_text);
            $q->where(function ($w) use ($term) {
                $w->where('r.number', 'like', "%{$term}%")
                    ->orWhere('r.order_id', 'like', "%{$term}%")
                    ->orWhere('r.id', $term);
                if (Schema::hasColumn('reports', 'request_order_id')) {
                    $w->orWhere('r.request_order_id', 'like', "%{$term}%");
                }
                if (Schema::hasColumn('reports', 'operator_id')) {
                    $w->orWhere('r.operator_id', 'like', "%{$term}%");
                }
            });
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

        $base = $this->baseQuery($request);
        $total = (clone $base)->count('r.id');

        $reports = (clone $base)
            ->select(
                'r.*',
                'u.outlet_name',
                'u.first_name',
                'u.mobile_number',
                'p.provider_name',
                'a.api_name'
            )
            ->orderByDesc('r.id')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $rows = '';
        if ($reports->count() > 0) {
            foreach ($reports as $list) {
                $userDetails = trim(($list->outlet_name ?: $list->first_name ?: 'User') . ' / ' . ($list->mobile_number ?: '-') . ' / ID:' . ($list->user_id ?: '-'));
                $dt = $list->transaction_date ?: $list->created_at;
                $rows .= '<tr>
                    <td><input type="checkbox" class="form-check-input row-check" value="' . e($list->id) . '"></td>
                    <td><strong>' . e($list->order_id ?: ('R' . $list->id)) . '</strong><br><small class="text-muted">#' . e($list->id) . '</small></td>
                    <td>' . e($dt) . '</td>
                    <td>' . e($userDetails) . '</td>
                    <td>' . e($list->provider_name ?: '-') . '</td>
                    <td>' . e($list->number ?: '-') . '</td>
                    <td><strong>₹' . number_format((float) ($list->total_amount ?: $list->amount), 2) . '</strong></td>
                    <td>₹' . number_format((float) $list->amount, 2) . '</td>
                    <td><span class="badge bg-warning text-dark">' . e(strtoupper($list->status ?: 'PENDING')) . '</span></td>
                    <td>' . e($list->api_name ?: '-') . '</td>
                    <td class="text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-resend" data-id="' . e($list->id) . '" title="Same API again">Resend</button>
                        <button type="button" class="btn btn-sm btn-primary btn-row-rehit" data-id="' . e($list->id) . '" data-api-id="' . e((string) ($list->api_id ?? '')) . '" title="Rehit with selected / auto API">Rehit</button>
                        <button type="button" class="btn btn-sm btn-outline-warning btn-check-callback" data-id="' . e($list->id) . '" data-order-id="' . e($list->order_id ?: '') . '">Callback</button>
                        <button type="button" class="btn btn-sm btn-outline-success btn-mark-success" data-id="' . e($list->id) . '" data-operator-id="' . e((string) ($list->operator_id ?? '')) . '">Success</button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-mark" data-id="' . e($list->id) . '" data-status="Failed">Fail</button>
                    </td>
                </tr>';
            }
        } else {
            $rows = '<tr><td colspan="11" class="text-center text-muted py-4">No data available in table</td></tr>';
        }

        $apiStats = '<tr><td colspan="4" class="text-center text-muted">No data</td></tr>';
        $operatorStats = '<tr><td colspan="3" class="text-center text-muted">No data</td></tr>';
        try {
            $apiStats = $this->apiWiseStats($request);
        } catch (\Throwable $e) {
        }
        try {
            $operatorStats = $this->operatorWiseStats($request);
        } catch (\Throwable $e) {
        }

        return response()->json([
            'type' => 'success',
            'rows' => $rows,
            'api_stats' => $apiStats,
            'operator_stats' => $operatorStats,
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

    private function apiWiseStats(Request $request): string
    {
        try {
            $rows = $this->baseQuery($request)
                ->select(
                    'r.api_id',
                    DB::raw('MAX(a.api_name) as api_name'),
                    DB::raw('COUNT(r.id) as total'),
                    DB::raw('COALESCE(SUM(r.total_amount), 0) as amount')
                )
                ->groupBy('r.api_id')
                ->orderByDesc('total')
                ->get();
        } catch (\Throwable $e) {
            return '<tr><td colspan="4" class="text-center text-danger">Could not load API stats</td></tr>';
        }

        if ($rows->isEmpty()) {
            return '<tr><td colspan="4" class="text-center text-muted">No data</td></tr>';
        }

        $html = '';
        $i = 1;
        foreach ($rows as $row) {
            $apiId = (int) ($row->api_id ?? 0);
            $name = $row->api_name ?: 'NO API';
            $html .= '<tr class="api-stat-row" data-api-id="'.$apiId.'" style="cursor:pointer">
                <td>'.$i++.'</td>
                <td>'.e($name).'</td>
                <td>Pending</td>
                <td>'.(int) $row->total.'</td>
            </tr>';
        }

        return $html;
    }

    private function operatorWiseStats(Request $request): string
    {
        try {
            $rows = $this->baseQuery($request)
                ->select(
                    'r.provider_id',
                    DB::raw('MAX(p.provider_name) as provider_name'),
                    DB::raw('COUNT(r.id) as pending')
                )
                ->groupBy('r.provider_id')
                ->orderByDesc('pending')
                ->get();
        } catch (\Throwable $e) {
            return '<tr><td colspan="3" class="text-center text-danger">Could not load operator stats</td></tr>';
        }

        if ($rows->isEmpty()) {
            return '<tr><td colspan="3" class="text-center text-muted">No data</td></tr>';
        }

        $html = '';
        $i = 1;
        foreach ($rows as $row) {
            $html .= '<tr>
                <td>'.$i++.'</td>
                <td>'.e($row->provider_name ?: 'NO OPERATOR').'</td>
                <td>'.(int) $row->pending.'</td>
            </tr>';
        }

        return $html;
    }

    public function bulkStatus(Request $request)
    {
        $ids = $request->ids;
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['type' => 'error', 'message' => 'Select at least one transaction']);
        }
        $status = $request->status;
        if (!in_array($status, ['Success', 'Failed'], true)) {
            return response()->json(['type' => 'error', 'message' => 'Invalid status']);
        }

        $operatorId = trim((string) ($request->operator_id ?? ''));
        if ($status === 'Success' && $operatorId === '') {
            return response()->json(['type' => 'error', 'message' => 'Operator Id is required for SUCCESS']);
        }

        $payload = [
            'status' => $status,
            'updated_at' => Carbon::now(),
        ];
        if ($status === 'Success' && Schema::hasColumn('reports', 'operator_id')) {
            $payload['operator_id'] = $operatorId;
            $payload['remark'] = 'Manual Success — Operator Id: '.$operatorId;
        }
        if ($status === 'Failed') {
            $payload['remark'] = 'Manual Failed';
            if ($operatorId !== '' && Schema::hasColumn('reports', 'operator_id')) {
                $payload['operator_id'] = $operatorId;
            }
        }
        if (!Schema::hasColumn('reports', 'is_manual')) {
            Schema::table('reports', function ($table) {
                $table->unsignedTinyInteger('is_manual')->default(0)->index();
            });
        }
        $payload['is_manual'] = 1;

        $pending = ['Pending', 'Under Proces', 'Under Process', 'Processing'];
        $reports = DB::table('reports')
            ->whereIn('id', array_map('intval', $ids))
            ->whereIn('status', $pending)
            ->get();

        $updated = 0;
        foreach ($reports as $row) {
            $ok = DB::table('reports')->where('id', $row->id)->whereIn('status', $pending)->update($payload);
            if (! $ok) {
                continue;
            }
            $updated++;
            if ($status === 'Success') {
                try {
                    \Helper::SetCommission($row->id);
                } catch (\Throwable $e) {
                }
            }
            if ($status === 'Failed') {
                try {
                    \Helper::refund_row($row->id);
                } catch (\Throwable $e) {
                }
            }
            try {
                \Helper::sendApiPartnerRechargeCallback($row->id);
            } catch (\Throwable $e) {
            }
            AdminAudit::log('recharge_status', 'pending_'.$status, [
                'ref_type' => 'report',
                'ref_id' => $row->id,
                'old' => $row->status,
                'new' => $status,
                'meta' => ['operator_id' => $operatorId],
            ]);
        }

        return response()->json([
            'type' => 'success',
            'message' => "Updated {$updated} transaction(s) to {$status}",
        ]);
    }

    public function resend(Request $request)
    {
        $id = (int) $request->id;
        if ($id <= 0) {
            return response()->json(['type' => 'error', 'message' => 'Invalid transaction']);
        }

        $report = DB::table('reports')->where('id', $id)->first();
        if (! $report) {
            return response()->json(['type' => 'error', 'message' => 'Transaction not found']);
        }
        if (! in_array($report->status, ['Pending', 'Under Proces', 'Under Process', 'Processing'], true)) {
            return response()->json(['type' => 'error', 'message' => 'Only pending transactions can be resent']);
        }

        $result = $this->sendToApi($report, (int) $report->api_id);
        if (empty($result['ok'])) {
            return response()->json(['type' => 'error', 'message' => $result['message'] ?? 'Resend failed']);
        }

        return response()->json([
            'type' => 'success',
            'message' => $result['message'],
        ]);
    }

    public function rehit(Request $request)
    {
        $ids = $request->ids;
        if (! is_array($ids) || empty($ids)) {
            // Single-row rehit: id=123
            if ($request->filled('id')) {
                $ids = [(int) $request->id];
            } else {
                return response()->json(['type' => 'error', 'message' => 'Select at least one transaction']);
            }
        }

        $modeChoice = strtolower(trim((string) ($request->rehit_api_id ?? '')));
        // same | auto | numeric api id
        if ($modeChoice === '' || $modeChoice === '0') {
            return response()->json(['type' => 'error', 'message' => 'Select Rehit API (Same / Auto / specific API)']);
        }

        $ok = 0;
        $fail = 0;
        $details = [];
        foreach (array_map('intval', $ids) as $id) {
            if ($id <= 0) {
                continue;
            }

            try {
                DB::beginTransaction();
                $report = DB::table('reports')->where('id', $id)->lockForUpdate()->first();
                if (! $report || ! in_array($report->status, ['Pending', 'Under Proces', 'Under Process', 'Processing'], true)) {
                    DB::rollBack();
                    $fail++;
                    $details[] = '#'.$id.': skipped (not pending)';
                    continue;
                }

                $resolved = $this->resolveRehitApiId($report, $modeChoice);
                if (empty($resolved['ok'])) {
                    DB::rollBack();
                    $fail++;
                    $details[] = '#'.$id.' '.($report->order_id ?? '').': '.($resolved['message'] ?? 'No API');
                    continue;
                }
                $apiId = (int) $resolved['api_id'];
                $apiName = (string) ($resolved['api_name'] ?? $apiId);

                DB::table('reports')->where('id', $id)->update([
                    'api_id' => $apiId,
                    'remark' => 'Rehit via '.$apiName,
                    'updated_at' => Carbon::now(),
                ]);
                $report->api_id = $apiId;
                DB::commit();

                $sent = $this->sendToApi($report, $apiId, 'REHIT');
                if (! empty($sent['ok'])) {
                    $ok++;
                    $details[] = ($report->order_id ?: '#'.$id).' → '.$apiName.' → '.($sent['status'] ?? 'Pending').(($sent['message'] ?? '') !== '' ? ' ('.$sent['message'].')' : '');
                } else {
                    $fail++;
                    $details[] = ($report->order_id ?: '#'.$id).' → '.$apiName.' → FAIL: '.($sent['message'] ?? 'Failed');
                }
            } catch (\Throwable $e) {
                try {
                    DB::rollBack();
                } catch (\Throwable $x) {
                }
                $fail++;
                $details[] = '#'.$id.': '.$e->getMessage();
            }
        }

        $summary = "Rehit done: {$ok} ok".($fail ? ", {$fail} failed" : '');
        if ($details !== []) {
            $summary .= "\n".implode("\n", array_slice($details, 0, 20));
            if (count($details) > 20) {
                $summary .= "\n… +".(count($details) - 20).' more';
            }
        }

        return response()->json([
            'type' => $ok > 0 ? 'success' : 'error',
            'message' => $summary,
            'ok' => $ok,
            'fail' => $fail,
            'details' => $details,
        ]);
    }

    /**
     * @return array{ok:bool, api_id?:int, api_name?:string, message?:string}
     */
    private function resolveRehitApiId(object $report, string $modeChoice): array
    {
        $apiId = 0;
        if ($modeChoice === 'same') {
            $apiId = (int) ($report->api_id ?? 0);
            if ($apiId <= 0) {
                return ['ok' => false, 'message' => 'Current API missing — pick a specific Rehit API'];
            }
        } elseif ($modeChoice === 'auto') {
            $apiId = $this->routingRehitApiId((int) ($report->provider_id ?? 0), (float) ($report->total_amount ?? $report->amount ?? 0));
            if ($apiId <= 0) {
                $apiId = (int) ($report->api_id ?? 0);
            }
            if ($apiId <= 0) {
                return ['ok' => false, 'message' => 'No Auto Rehit API in routing — set General Routing → Rehit API'];
            }
        } else {
            $apiId = (int) $modeChoice;
        }

        $api = DB::table('apis')->where('id', $apiId)->where('status', 1)->first();
        if (! $api) {
            return ['ok' => false, 'message' => 'API #'.$apiId.' not found or inactive'];
        }
        $apiType = strtolower(trim((string) ($api->api_type ?? 'recharge')));
        if ($apiType !== '' && $apiType !== 'recharge') {
            return ['ok' => false, 'message' => 'Only Recharge API allowed'];
        }

        return ['ok' => true, 'api_id' => (int) $api->id, 'api_name' => (string) $api->api_name];
    }

    private function routingRehitApiId(int $providerId, float $amount): int
    {
        if ($providerId <= 0 || ! Schema::hasTable('general_routings')) {
            return 0;
        }
        try {
            $rows = DB::table('general_routings')
                ->where('status', 'Active')
                ->where('rehit_api_id', '>', 0)
                ->where(function ($q) use ($providerId) {
                    $q->where('provider_id', $providerId)
                        ->orWhere('provider_id', 0)
                        ->orWhereNull('provider_id');
                })
                ->orderByDesc('priority')
                ->orderBy('id')
                ->limit(20)
                ->get(['rehit_api_id', 'amounts', 'provider_id']);
            foreach ($rows as $row) {
                if ((int) ($row->provider_id ?? 0) !== 0 && (int) $row->provider_id !== $providerId) {
                    continue;
                }
                if (! $this->amountMatchesRouting($amount, (string) ($row->amounts ?? ''))) {
                    continue;
                }
                $id = (int) $row->rehit_api_id;
                if ($id > 0) {
                    return $id;
                }
            }
        } catch (\Throwable $e) {
        }

        return 0;
    }

    private function amountMatchesRouting(float $amount, string $amounts): bool
    {
        $amounts = trim($amounts);
        if ($amounts === '' || $amounts === '*' || strcasecmp($amounts, 'all') === 0) {
            return true;
        }
        foreach (preg_split('/\s*,\s*/', $amounts) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (str_contains($part, '-')) {
                [$a, $b] = array_pad(explode('-', $part, 2), 2, '');
                if (is_numeric($a) && is_numeric($b) && $amount >= (float) $a && $amount <= (float) $b) {
                    return true;
                }
            } elseif (is_numeric($part) && abs($amount - (float) $part) < 0.001) {
                return true;
            }
        }

        return false;
    }

    private function sendToApi(object $report, int $apiId, string $mode = 'RESEND'): array
    {
        $api = DB::table('apis')->where('id', $apiId)->first();
        if (! $api || (int) $api->status !== 1) {
            return ['ok' => false, 'message' => 'Active API not found for this transaction'];
        }
        $apiType = strtolower(trim((string) ($api->api_type ?? 'recharge')));
        if ($apiType !== '' && $apiType !== 'recharge') {
            return ['ok' => false, 'message' => 'Only Recharge API can be resent'];
        }
        $url = trim((string) ($api->api_url ?? ''));
        if ($url === '') {
            return ['ok' => false, 'message' => 'API URL is empty'];
        }

        $isRehit = strtoupper($mode) === 'REHIT';

        try {
            $providerCode = \Helper::ApiProviderCode($api->id, $report->provider_id);
            $stateCode = \Helper::ApiStateCode($api->id, $report->state_id ?? 0);

            $url = str_replace('{API_USERNAME}', (string) $api->api_username, $url);
            $url = str_replace('{API_PASSWORD}', (string) $api->api_password, $url);
            $url = str_replace('{API_KEY}', (string) $api->api_key, $url);
            $url = str_replace('{NUMBER}', (string) $report->number, $url);
            $url = str_replace('{PROVIDER_CODE}', (string) $providerCode, $url);
            $url = str_replace('{STATE_CODE}', (string) $stateCode, $url);
            $url = str_replace('{AMOUNT}', (string) ($report->total_amount ?? $report->amount), $url);
            $url = str_replace('{ORDER_ID}', (string) $report->order_id, $url);

            if (preg_match('/\{[A-Z0-9_]+\}/', $url)) {
                return ['ok' => false, 'message' => 'API URL still has unreplaced placeholders. Check provider/state codes for this API.'];
            }
            if ((string) $providerCode === '' || (string) $providerCode === '0') {
                return ['ok' => false, 'message' => 'Provider code missing for API "'.($api->api_name ?? $apiId).'". Set operator code in API Provider Codes.'];
            }

            $method = strtoupper(trim($api->api_method ?: 'GET'));
            $modal = $isRehit ? 'REHIT' : 'RESEND';
            $result = \Helper::curl($url, $method, '', [], 'yes', $modal, $report->order_id);

            $response = (string) ($result['response'] ?? '');
            $curlError = (string) ($result['error'] ?? '');
            $httpCode = (int) ($result['code'] ?? 0);
            if ($response === '' && $curlError !== '') {
                return ['ok' => false, 'message' => 'API error: '.$curlError];
            }
            if ($response === '' && $httpCode >= 500) {
                return ['ok' => false, 'message' => 'API HTTP '.$httpCode.' empty response'];
            }

            $status = 'Pending';
            $operatorId = '';
            $apiOperatorId = '';
            $apiMsg = '';
            $remark = ($isRehit ? 'Rehit' : 'Resend').' via '.($api->api_name ?? 'API');

            if ($response !== '' && strtoupper((string) ($api->api_format ?? '')) === 'JSON') {
                $data = json_decode($response, true);
                if (is_array($data)) {
                    $actual = \Helper::apiArrayGet($data, $api->status_value ?? '');
                    if ($actual === null && isset($data['Response'])) {
                        $actual = $data['Response'];
                    }
                    if ($actual === null) {
                        $actual = \Helper::apiArrayGet($data, $api->error_value ?? '');
                    }
                    $mapped = \Helper::mapApiLiveStatus($api, $actual, $data);
                    if ($mapped) {
                        $status = $mapped;
                    }
                    $operatorId = (string) (\Helper::apiArrayGet($data, $api->operator_id_value ?? '') ?? '');
                    $apiOperatorId = (string) (\Helper::apiArrayGet($data, $api->order_id_value ?? '') ?? '');
                    $apiMsg = (string) ($data['Message'] ?? $data['message'] ?? $data['msg'] ?? '');
                    if ($status === 'Success') {
                        $remark = ($isRehit ? 'Rehit' : 'Resend').' successful'.($apiMsg ? ' - '.$apiMsg : '');
                    } elseif ($status === 'Failed') {
                        // Rehit: keep Pending + no auto-refund (first hit may still be live at operator).
                        if ($isRehit) {
                            $status = 'Pending';
                            $remark = 'Rehit API returned Failed — left Pending (no refund). Check Callback / mark Fail manually.'.($apiMsg ? ' — '.$apiMsg : '');
                        } else {
                            $remark = 'Resend failed'.($apiMsg ? ' - '.$apiMsg : '');
                        }
                    } else {
                        $remark = ($isRehit ? 'Rehit' : 'Resend').' via '.($api->api_name ?? 'API').' — Pending'.($apiMsg ? ' - '.$apiMsg : '');
                    }
                }
            } elseif ($response === '') {
                $remark = ($isRehit ? 'Rehit' : 'Resend').' via '.($api->api_name ?? 'API').' — no response, left Pending';
            }

            $update = [
                'api_id' => $api->id,
                'status' => $status,
                'remark' => $remark,
                'updated_at' => Carbon::now(),
            ];
            if ($operatorId !== '' && Schema::hasColumn('reports', 'operator_id')) {
                $update['operator_id'] = $operatorId;
            }
            if ($apiOperatorId !== '' && Schema::hasColumn('reports', 'api_operator_id')) {
                $update['api_operator_id'] = $apiOperatorId;
            }
            DB::table('reports')->where('id', $report->id)->update($update);
            $report->operator_id = $operatorId !== '' ? $operatorId : ($report->operator_id ?? null);
            RehitRechargeHistoryController::logAttempt($report, (int) $api->id, $mode);

            if ($status === 'Success') {
                try {
                    \Helper::SetCommission($report->id);
                } catch (\Throwable $e) {
                }
                try {
                    \Helper::sendApiPartnerRechargeCallback($report->id);
                } catch (\Throwable $e) {
                }
            }
            // Resend only: Failed → refund. Rehit never auto-refunds.
            if (! $isRehit && $status === 'Failed') {
                try {
                    \Helper::refund_row($report->id);
                } catch (\Throwable $e) {
                }
                try {
                    \Helper::sendApiPartnerRechargeCallback($report->id);
                } catch (\Throwable $e) {
                }
            }

            return [
                'ok' => true,
                'status' => $status,
                'message' => $apiMsg !== '' ? $apiMsg : (($isRehit ? 'Rehit' : 'Resend').' → '.$status),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
