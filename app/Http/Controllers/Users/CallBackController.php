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
                'txnid' => (string) ($data['order_id'] ?? $data['request_order_id'] ?? $data['txnid'] ?? ''),
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
        $operatorId = (string) (\helpers::apiArrayGet($data, $operatorKey) ?? $data['opid'] ?? $data['operator_id'] ?? '');
        $orderRef = (string) (\helpers::apiArrayGet($data, $orderKey)
            ?? $data['order_id']
            ?? $data['OrderId']
            ?? $data['request_order_id']
            ?? $data['txnid']
            ?? '');

        $update = [
            'status' => $mapped,
            'operator_id' => $operatorId,
            'callback_response' => json_encode($data),
            'updated_at' => Carbon::now(),
        ];
        if ($operatorId !== '' && Schema::hasColumn('reports', 'api_operator_id') && in_array($mapped, ['Success', 'Failed', 'Refunded'], true)) {
            if (($api->callback_operator_id_value ?? '') === ($api->callback_order_id_value ?? '')) {
                // keep operator_id as mapped; do not overwrite api_operator_id blindly
            }
        }

        try {
            DB::beginTransaction();
            $report = $this->findPendingReport((int) $api_id, $orderRef, $operatorId);
            if (! $report) {
                DB::commit();
                return response()->json(['type' => 'error', 'message' => 'record not found or already finalized.']);
            }

            if ($update['status'] === 'Refunded') {
                $successReport = DB::table('reports')
                    ->where('order_id', $report->order_id)
                    ->where('status', 'Success')
                    ->where(function ($q) {
                        $q->where('parent__id', 0)->orWhereNull('parent__id');
                    })
                    ->first();
                if ($successReport) {
                    DB::table('reports')->where('id', $successReport->id)->update($update);
                    DB::commit();
                    \helpers::refund_row($successReport->id);
                    \helpers::ReverseCommission($successReport->id);
                    $c_report = DB::table('complaints')->where('order_id', $report->order_id)->where('status', 'Open')->first();
                    if ($c_report) {
                        DB::table('complaints')->where('id', $c_report->id)->update([
                            'decision_by' => 1,
                            'decision_remark' => 'Recharge Refunded',
                            'status' => 'Sloved',
                            'decision_date' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                        if (isset($c_report->report_id)) {
                            DB::table('reports')->where('id', $c_report->report_id)->update(['complaint_id' => 0]);
                        }
                    }
                    \helpers::sendApiPartnerRechargeCallback($successReport->id);

                    return response()->json(['type' => 'success', 'message' => 'recharge refunded processed']);
                }
            }

            if ($mapped === 'Pending') {
                DB::table('reports')->where('id', $report->id)->update([
                    'callback_response' => $update['callback_response'],
                    'updated_at' => Carbon::now(),
                ]);
                DB::commit();

                return response()->json(['type' => 'success', 'message' => 'status left as Pending']);
            }

            DB::table('reports')->where('id', $report->id)->update($update);
            DB::commit();

            if ($update['status'] === 'Success') {
                \helpers::SetCommission($report->id);
                return response()->json(['type' => 'success', 'message' => 'status updated to Success']);
            }
            if ($update['status'] === 'Failed') {
                \helpers::closeOpenComplaintsForReport((int) $report->id, 'Recharge failed before completion.');
                \helpers::refund_row($report->id);
                return response()->json(['type' => 'success', 'message' => 'status updated to Failed and refunded']);
            }

            \helpers::sendApiPartnerRechargeCallback($report->id);

            return response()->json(['type' => 'success', 'message' => 'status updated']);
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
}
