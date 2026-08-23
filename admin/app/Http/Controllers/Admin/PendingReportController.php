<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
                    ->orWhere('r.request_order_id', 'like', "%{$term}%")
                    ->orWhere('r.operator_id', 'like', "%{$term}%")
                    ->orWhere('r.id', $term);
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
        $total = (clone $base)->count();

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
                    <td>₹' . number_format((float) $list->amount, 2) . '</td>
                    <td><span class="badge bg-warning text-dark">' . e(strtoupper($list->status ?: 'PENDING')) . '</span></td>
                    <td>' . e($list->api_name ?: '-') . '</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-resend" data-id="' . e($list->id) . '">Resend</button>
                        <button type="button" class="btn btn-sm btn-outline-success btn-mark" data-id="' . e($list->id) . '" data-status="Success">Success</button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-mark" data-id="' . e($list->id) . '" data-status="Failed">Fail</button>
                    </td>
                </tr>';
            }
        } else {
            $rows = '<tr><td colspan="10" class="text-center text-muted py-4">No data available in table</td></tr>';
        }

        $apiStats = $this->apiWiseStats($request);
        $operatorStats = $this->operatorWiseStats($request);

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
        $rows = $this->baseQuery($request)
            ->select('a.api_name', DB::raw('COUNT(r.id) as total'))
            ->groupBy('a.api_name')
            ->orderByDesc('total')
            ->get();

        if ($rows->isEmpty()) {
            return '<tr><td colspan="4" class="text-center text-muted">No data</td></tr>';
        }

        $html = '';
        $i = 1;
        foreach ($rows as $row) {
            $name = $row->api_name ?: 'NO API';
            $html .= '<tr>
                <td>' . $i++ . '</td>
                <td>' . e($name) . '</td>
                <td>Pending</td>
                <td>' . (int) $row->total . '</td>
            </tr>';
        }
        return $html;
    }

    private function operatorWiseStats(Request $request): string
    {
        $rows = $this->baseQuery($request)
            ->select('p.provider_name', DB::raw('COUNT(r.id) as pending'))
            ->groupBy('p.provider_name')
            ->orderByDesc('pending')
            ->get();

        if ($rows->isEmpty()) {
            return '<tr><td colspan="3" class="text-center text-muted">No data</td></tr>';
        }

        $html = '';
        $i = 1;
        foreach ($rows as $row) {
            $html .= '<tr>
                <td>' . $i++ . '</td>
                <td>' . e($row->provider_name ?: 'NO OPERATOR') . '</td>
                <td>' . (int) $row->pending . '</td>
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

        $payload = [
            'status' => $status,
            'updated_at' => Carbon::now(),
        ];
        if (!Schema::hasColumn('reports', 'is_manual')) {
            Schema::table('reports', function ($table) {
                $table->unsignedTinyInteger('is_manual')->default(0)->index();
            });
        }
        $payload['is_manual'] = 1;

        $updated = DB::table('reports')
            ->whereIn('id', array_map('intval', $ids))
            ->whereIn('status', ['Pending', 'Under Proces', 'Under Process'])
            ->update($payload);

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
        if (!$report) {
            return response()->json(['type' => 'error', 'message' => 'Transaction not found']);
        }
        if (!in_array($report->status, ['Pending', 'Under Proces', 'Under Process', 'Processing'], true)) {
            return response()->json(['type' => 'error', 'message' => 'Only pending transactions can be resent']);
        }

        $api = DB::table('apis')->where('id', $report->api_id)->first();
        if (!$api || (int) $api->status !== 1) {
            return response()->json(['type' => 'error', 'message' => 'Active API not found for this transaction']);
        }
        $apiType = strtolower((string) ($api->api_type ?? 'recharge'));
        if ($apiType !== '' && $apiType !== 'recharge') {
            return response()->json(['type' => 'error', 'message' => 'Only Recharge API can be resent']);
        }

        try {
            $providerCode = \helpers::ApiProviderCode($api->id, $report->provider_id);
            $stateCode = \helpers::ApiStateCode($api->id, $report->state_id);

            $url = (string) $api->api_url;
            $url = str_replace('{API_USERNAME}', (string) $api->api_username, $url);
            $url = str_replace('{API_PASSWORD}', (string) $api->api_password, $url);
            $url = str_replace('{API_KEY}', (string) $api->api_key, $url);
            $url = str_replace('{NUMBER}', (string) $report->number, $url);
            $url = str_replace('{PROVIDER_CODE}', (string) $providerCode, $url);
            $url = str_replace('{STATE_CODE}', (string) $stateCode, $url);
            $url = str_replace('{AMOUNT}', (string) ($report->total_amount ?? $report->amount), $url);
            $url = str_replace('{ORDER_ID}', (string) $report->order_id, $url);

            $method = strtoupper(trim($api->api_method ?: 'GET'));
            $logFlag = ((int) ($api->store_log ?? 0) === 1) ? 'yes' : 'no';
            $result = \helpers::curl($url, $method, '', [], $logFlag, $report->transaction_type ?: 'RECHARGE', $report->order_id);

            $response = (string) ($result['response'] ?? '');
            $status = 'Pending';
            $operatorId = '';
            $remark = 'Resent to ' . ($api->api_name ?? 'API');

            if ($response !== '' && $api->api_format === 'JSON') {
                $data = json_decode($response, true);
                if (is_array($data)) {
                    $statusKey = $api->status_value;
                    if ($statusKey && isset($data[$statusKey])) {
                        $val = $data[$statusKey];
                        if ($val == $api->success_value) {
                            $status = 'Success';
                        } elseif ($val == $api->failed_value) {
                            $status = 'Failed';
                        }
                    }
                    if (!empty($api->operator_id_value) && isset($data[$api->operator_id_value])) {
                        $operatorId = (string) $data[$api->operator_id_value];
                    }
                }
            }

            $update = [
                'status' => $status,
                'remark' => $remark,
                'updated_at' => Carbon::now(),
            ];
            if ($operatorId !== '' && Schema::hasColumn('reports', 'operator_id')) {
                $update['operator_id'] = $operatorId;
            }
            DB::table('reports')->where('id', $report->id)->update($update);

            return response()->json([
                'type' => 'success',
                'message' => 'Resent to ' . ($api->api_name ?? 'API') . ' — ' . $status,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function rehit(Request $request)
    {
        $ids = $request->ids;
        $apiId = (int) $request->rehit_api_id;
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['type' => 'error', 'message' => 'Select at least one transaction']);
        }
        if ($apiId <= 0) {
            return response()->json(['type' => 'error', 'message' => 'Select Rehit API']);
        }

        $api = DB::table('apis')->where('id', $apiId)->first();
        if (!$api) {
            return response()->json(['type' => 'error', 'message' => 'Invalid Rehit API']);
        }
        $apiType = strtolower((string) ($api->api_type ?? 'recharge'));
        if ($apiType !== '' && $apiType !== 'recharge') {
            return response()->json(['type' => 'error', 'message' => 'Only Recharge API can be used for resend/rehit']);
        }

        $data = [
            'api_id' => $apiId,
            'updated_at' => Carbon::now(),
        ];
        if (Schema::hasColumn('reports', 'remark')) {
            $data['remark'] = 'Rehit via ' . ($api->api_name ?? $apiId);
        }

        $updated = DB::table('reports')
            ->whereIn('id', array_map('intval', $ids))
            ->whereIn('status', ['Pending', 'Under Proces', 'Under Process'])
            ->update($data);

        return response()->json([
            'type' => 'success',
            'message' => "Rehit queued for {$updated} transaction(s) on {$api->api_name}",
        ]);
    }
}
