<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CallBackController extends Controller
{
    public function rechargeCallback(Request $post, $api_id)
    {
        $data = $this->callbackPayload($post);

        try {
            DB::table('apilogs')->insert([
                'url' => '/recharge-callback/'.$api_id,
                'modal' => 'RechargeCallback',
                'txnid' => $this->callbackOrderRef($data),
                'header' => json_encode(\helpers::safeRequestMeta()),
                'request' => json_encode($data),
                'response' => '',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
        }

        if ($data === []) {
            return response()->json(['type' => 'error', 'message' => 'param not found']);
        }

        $api = DB::table('apis')->where('id', $api_id)->first();
        if (! $api) {
            return response()->json(['type' => 'error', 'message' => 'not found api.']);
        }

        if (! \helpers::apiSwitchOn($api, 'callback_switch')) {
            return response()->json(['type' => 'error', 'message' => 'callback switch is off for this api.']);
        }

        $statusKey = $api->callback_status_value;
        $operatorKey = $api->callback_operator_id_value;
        $orderKey = $api->callback_order_id_value;

        $actual = \helpers::apiArrayGet($data, $statusKey);
        if ($actual === null && isset($data['Response'])) {
            $actual = $data['Response'];
        }
        if ($actual === null) {
            $actual = $data['status'] ?? $data['Status'] ?? $data['STATUS'] ?? null;
        }

        $mapped = \helpers::mapApiCallbackStatus($api, $actual);
        $operatorId = \helpers::sanitizeOperatorId(
            \helpers::apiArrayGet($data, $operatorKey)
            ?? $data['opid']
            ?? $data['operator_id']
            ?? $data['operatorId']
            ?? ''
        );
        $orderRef = (string) (\helpers::apiArrayGet($data, $orderKey) ?: $this->callbackOrderRef($data));

        $update = [
            'status' => $mapped,
            'callback_response' => json_encode($data),
            'updated_at' => Carbon::now(),
        ];
        if ($operatorId !== '') {
            $update['operator_id'] = $operatorId;
            if (Schema::hasColumn('reports', 'api_operator_id')
                && in_array($mapped, ['Success', 'Failed', 'Refunded'], true)
                && ($api->callback_operator_id_value ?? '') !== ($api->callback_order_id_value ?? '')) {
                $update['api_operator_id'] = $operatorId;
            }
        }

        try {
            DB::beginTransaction();
            $report = $this->findPendingReport((int) $api_id, $orderRef, $operatorId);
            $fromFailed = false;
            $fromSuccess = false;
            $partnerOnly = false;
            if (! $report && $mapped === 'Success') {
                $report = $this->findReportByStatus((int) $api_id, $orderRef, $operatorId, ['Failed', 'Failure', 'Refunded']);
                $fromFailed = (bool) $report;
            }
            if (! $report && $mapped === 'Refunded') {
                $report = $this->findReportByStatus((int) $api_id, $orderRef, $operatorId, ['Success']);
                $fromSuccess = (bool) $report;
            }
            // Already Success but partner callback not delivered yet — still notify partner.
            if (! $report && $mapped === 'Success') {
                $report = $this->findReportByStatus((int) $api_id, $orderRef, $operatorId, ['Success']);
                if ($report && (int) ($report->callback_status ?? 0) !== 1) {
                    $partnerOnly = true;
                } elseif ($report) {
                    $report = null;
                }
            }
            if (! $report) {
                DB::commit();
                return response()->json(['type' => 'error', 'message' => 'record not found or already finalized.']);
            }

            if ($mapped === 'Pending') {
                DB::table('reports')->where('id', $report->id)->update([
                    'callback_response' => $update['callback_response'],
                    'updated_at' => Carbon::now(),
                ]);
                DB::commit();

                return response()->json(['type' => 'success', 'message' => 'status left as Pending']);
            }

            if ($partnerOnly) {
                $touch = [
                    'callback_response' => $update['callback_response'],
                    'updated_at' => Carbon::now(),
                ];
                if ($operatorId !== '' && empty($report->operator_id)) {
                    $touch['operator_id'] = $operatorId;
                }
                DB::table('reports')->where('id', $report->id)->update($touch);
                DB::commit();
                \helpers::sendApiPartnerRechargeCallback($report->id);

                return response()->json(['type' => 'success', 'message' => 'partner callback sent for existing Success']);
            }

            if (($fromFailed || $fromSuccess) && Schema::hasColumn('reports', 'callback_status')) {
                $update['callback_status'] = 0;
            }
            DB::table('reports')->where('id', $report->id)->update($update);
            DB::commit();

            if ($mapped === 'Success') {
                if ($fromFailed) {
                    \helpers::reverseRefund($report->id);
                    \helpers::recordFailToSuccess($report, 'callback', json_encode($data), 'Supplier callback Failed → Success');
                }
                $hasCommission = DB::table('reports')
                    ->where('parent__Id', $report->id)
                    ->where('transaction_type', 'Commission')
                    ->exists();
                if (! $hasCommission) {
                    \helpers::SetCommission($report->id);
                }
                \helpers::sendApiPartnerRechargeCallback($report->id);

                return response()->json(['type' => 'success', 'message' => 'status updated to Success']);
            }

            if ($fromSuccess) {
                \helpers::ReverseCommission($report->id);
            }
            \helpers::closeOpenComplaintsForReport((int) $report->id, 'Recharge '.strtolower($mapped).' by operator.');
            \helpers::refund_row($report->id);
            \helpers::sendApiPartnerRechargeCallback($report->id);

            return response()->json(['type' => 'success', 'message' => 'status updated to '.$mapped.' and refunded']);
        } catch (\Throwable $e) {
            DB::rollBack();
            try {
                DB::table('apilogs')->insert([
                    'url' => '/recharge-callback-error/'.$api_id,
                    'modal' => 'RechargeCallbackError',
                    'txnid' => $orderRef,
                    'header' => json_encode($_SERVER),
                    'request' => json_encode($data),
                    'response' => $e->getMessage(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            } catch (\Throwable $x) {
            }

            return response()->json(['type' => 'error', 'message' => 'callback processing error']);
        }
    }

    private function callbackPayload(Request $request): array
    {
        $data = $request->all();
        $raw = trim((string) $request->getContent());
        if ($raw !== '') {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $data = array_merge($data, $json);
                foreach (['data', 'Data', 'payload', 'result', 'Result', 'response', 'Response'] as $wrap) {
                    if (isset($json[$wrap]) && is_array($json[$wrap])) {
                        $data = array_merge($data, $json[$wrap]);
                    }
                }
            }
        }

        return is_array($data) ? $data : [];
    }

    /**
     * Resolve our order / request id from supplier callback body (handles camelCase keys).
     */
    private function callbackOrderRef(array $data): string
    {
        foreach ([
            'order_id', 'OrderId', 'ORDER_ID', 'orderId',
            'refId', 'RefId', 'REFID', 'ref_id', 'reference', 'Reference',
            'request_order_id', 'requestOrderId', 'RequestOrderId',
            'txnid', 'txnId', 'TxnId', 'TXNID', 'transactionId', 'TransactionId',
            'yourRequestId', 'AGENTID', 'AgentId',
        ] as $key) {
            $val = trim((string) ($data[$key] ?? ''));
            if ($val !== '' && strcasecmp($val, 'null') !== 0) {
                return $val;
            }
        }

        return '';
    }

    private function findPendingReport(int $apiId, string $orderRef, string $operatorId)
    {
        $pending = \helpers::rechargePendingStatuses();
        $candidates = [];
        if ($orderRef !== '') {
            $candidates[] = ['api_id' => $apiId, 'order_id' => $orderRef];
            if (Schema::hasColumn('reports', 'request_order_id')) {
                $candidates[] = ['api_id' => $apiId, 'request_order_id' => $orderRef];
            }
            if (Schema::hasColumn('reports', 'api_operator_id')) {
                $candidates[] = ['api_id' => $apiId, 'api_operator_id' => $orderRef];
            }
            $candidates[] = ['api_id' => $apiId, 'operator_id' => $orderRef];
            $candidates[] = ['order_id' => $orderRef];
        }
        if ($operatorId !== '') {
            $candidates[] = ['api_id' => $apiId, 'operator_id' => $operatorId];
        }

        foreach ($candidates as $where) {
            $q = DB::table('reports')->whereIn('status', $pending);
            foreach ($where as $col => $val) {
                $q->where($col, $val);
            }
            $report = $q->lockForUpdate()->first();
            if ($report) {
                return $report;
            }
        }

        return null;
    }

    private function findReportByStatus(int $apiId, string $orderRef, string $operatorId, array $statuses)
    {
        $candidates = [];
        if ($orderRef !== '') {
            $candidates[] = ['api_id' => $apiId, 'order_id' => $orderRef];
            $candidates[] = ['order_id' => $orderRef];
        }
        if ($operatorId !== '') {
            $candidates[] = ['api_id' => $apiId, 'operator_id' => $operatorId];
        }
        foreach ($candidates as $where) {
            $q = DB::table('reports')
                ->whereIn('status', $statuses)
                ->where('transaction_type', 'Recharge');
            foreach ($where as $col => $val) {
                $q->where($col, $val);
            }
            $report = $q->lockForUpdate()->first();
            if ($report) {
                return $report;
            }
        }

        return null;
    }
}
