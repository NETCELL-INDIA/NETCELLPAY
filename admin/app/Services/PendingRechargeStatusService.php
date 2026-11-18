<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PendingRechargeStatusService
{
    public static function ensureColumns(): void
    {
        try {
            if (Schema::hasTable('apis') && ! Schema::hasColumn('apis', 'status_check_url')) {
                DB::statement('ALTER TABLE `apis` ADD COLUMN `status_check_url` TEXT NULL');
            }
            if (Schema::hasTable('apis') && ! Schema::hasColumn('apis', 'status_check_method')) {
                DB::statement("ALTER TABLE `apis` ADD COLUMN `status_check_method` VARCHAR(8) NULL DEFAULT 'GET'");
            }
            if (Schema::hasTable('reports') && ! Schema::hasColumn('reports', 'status_checked_at')) {
                DB::statement('ALTER TABLE `reports` ADD COLUMN `status_checked_at` DATETIME NULL');
            }
        } catch (\Throwable $e) {
        }
    }

    public static function checkOne(int $id): array
    {
        self::ensureColumns();
        if ($id <= 0) {
            return ['ok' => false, 'id' => $id, 'message' => 'Invalid transaction'];
        }

        $pending = ['Pending', 'Under Process', 'Under Proces', 'Processing'];
        $report = DB::table('reports')->where('id', $id)->first();
        if (! $report || ! in_array((string) $report->status, $pending, true)) {
            return ['ok' => false, 'id' => $id, 'message' => 'Not pending'];
        }

        $api = DB::table('apis')->where('id', (int) $report->api_id)->first();
        if (! $api) {
            self::touchChecked($id);

            return ['ok' => false, 'id' => $id, 'message' => 'Status check URL is not set for this API'];
        }
        $statusUrl = trim((string) ($api->status_check_url ?? ''));
        $rechargeTemplate = trim((string) ($api->api_url ?? ''));
        if ($statusUrl === '' || $statusUrl === '0' || $statusUrl === $rechargeTemplate) {
            $derived = \Helper::realChargeDerivedUrl($api, 'GetRechargeStatus');
            if (is_string($derived) && $derived !== '') {
                $statusUrl = $derived;
            }
        }
        if (! $api || $statusUrl === '') {
            $logged = self::successFromLatestLog($report, $api);
            if ($logged !== null) {
                return self::applyMappedStatus($id, $api ?? (object) ['operator_id_value' => '', 'id' => 0], $logged, 'Success');
            }
            self::touchChecked($id);

            return ['ok' => false, 'id' => $id, 'message' => 'Status check URL is not set for this API'];
        }
        if ($rechargeTemplate !== '' && $statusUrl === $rechargeTemplate) {
            self::touchChecked($id);

            return ['ok' => false, 'id' => $id, 'message' => 'Status URL matches the recharge URL, so it was not called'];
        }

        $providerCode = '';
        $stateCode = '';
        try {
            $providerCode = (string) \Helper::ApiProviderCode((int) $api->id, (int) $report->provider_id);
            $stateCode = (string) \Helper::ApiStateCode((int) $api->id, (int) ($report->state_id ?? 0));
        } catch (\Throwable $e) {
        }

        $url = \Helper::applyApiUrlPlaceholders($statusUrl, $api, $report, $providerCode, $stateCode);
        $rechargeUrl = \Helper::applyApiUrlPlaceholders((string) ($api->api_url ?? ''), $api, $report, $providerCode, $stateCode);
        if ($rechargeUrl !== '' && $url === $rechargeUrl) {
            self::touchChecked($id);

            return ['ok' => false, 'id' => $id, 'message' => 'Status URL matches the recharge URL, so it was not called'];
        }

        $method = strtoupper(trim((string) ($api->status_check_method ?? 'GET')));
        if (! in_array($method, ['GET', 'POST'], true)) {
            $method = 'GET';
        }

        self::touchChecked($id);
        $result = \Helper::curl($url, $method, '', [], 'yes', 'StatusCheck', (string) ($report->order_id ?: $id));
        $body = (string) ($result['response'] ?? '');
        $data = $body !== '' ? \Helper::supplierPayload($body) : [];
        if ($data === []) {
            $logged = self::successFromLatestLog($report, $api);
            if ($logged !== null) {
                return self::applyMappedStatus($id, $api, $logged, 'Success');
            }
            $why = ($body === '' || ! empty($result['error']))
                ? 'Status API did not respond. Order left pending.'
                : 'Status response was not JSON or XML. Order left pending.';

            return ['ok' => false, 'id' => $id, 'message' => $why];
        }

        $actual = \Helper::apiArrayGet($data, $api->status_value ?? '');
        if ($actual === null) {
            $actual = \Helper::apiArrayGet($data, strtolower((string) ($api->status_value ?? '')));
        }
        if ($actual === null && isset($data['Response']) && ! is_array($data['Response'])) {
            $actual = $data['Response'];
        }
        if ($actual === null && isset($data['status']) && ! is_array($data['status'])) {
            $actual = $data['status'];
        }
        $remark = strtolower(trim((string) ($data['remark'] ?? $data['message'] ?? $data['Message'] ?? $data['response'] ?? '')));
        $notFound = preg_match('/not found|invalid req|no record|no transaction|does not exist|no recharge/', $remark) === 1;
        $decision = \Helper::realChargeStatusDecision($api, $report, $data, $actual);
        $mapped = $decision ?? \Helper::mapApiLiveStatus($api, $actual, $data);
        if (\Helper::isMroboticsPayload($data)) {
            $robo = \Helper::mroboticsOutcome($data);
            if ($robo && ! $robo['session'] && $robo['status'] === 'Success') {
                $mapped = 'Success';
            } elseif ($robo && $robo['final_failure']) {
                $mapped = 'Failed';
            }
        }
        if ($notFound) {
            $mapped = ($mapped === 'Failed') ? 'Failed' : 'Pending';
        } elseif (self::payloadSaysSuccess($data, $api)) {
            $mapped = 'Success';
        }
        if (($mapped === null || $mapped === 'Pending') && ! self::payloadSaysFailed($data)) {
            $logged = self::successFromLatestLog($report, $api);
            if ($logged !== null) {
                $data = $logged;
                $mapped = 'Success';
            }
        }
        if ($mapped === null || $mapped === 'Pending') {
            $shown = trim(strip_tags((string) ($actual ?? '')));
            $shown = trim((string) preg_replace('/\s+/', ' ', $shown));
            if ($shown === '') {
                $shown = 'pending';
            }
            if (strlen($shown) > 80) {
                $shown = substr($shown, 0, 80);
            }

            return ['ok' => true, 'id' => $id, 'status' => 'Pending', 'message' => 'Supplier status: '.$shown];
        }

        return self::applyMappedStatus($id, $api, $data, $mapped);
    }

    private static function applyMappedStatus(int $id, object $api, array $data, string $mapped): array
    {
        $pending = ['Pending', 'Under Process', 'Under Proces', 'Processing'];
        try {
            DB::beginTransaction();
            $report = DB::table('reports')->where('id', $id)->lockForUpdate()->first();
            if (! $report || ! in_array((string) $report->status, $pending, true)) {
                DB::commit();

                return ['ok' => false, 'id' => $id, 'message' => 'Status changed before the check finished'];
            }

            $opId = \Helper::sanitizeOperatorId(\Helper::apiArrayGet($data, $api->operator_id_value ?? '') ?? '');
            if ($opId === '') {
                $opId = \Helper::sanitizeOperatorId($data['field1'] ?? $data['opid'] ?? $data['operatorRef'] ?? $data['txnId'] ?? $data['txid'] ?? '');
            }
            $msg = trim((string) ($data['remark'] ?? $data['Message'] ?? $data['message'] ?? ''));
            $status = $mapped === 'Success' ? 'Success' : 'Failed';
            $update = [
                'status' => $status,
                'remark' => 'Status check: '.$status.($msg !== '' ? ' - '.$msg : ''),
                'updated_at' => Carbon::now(),
                'status_checked_at' => Carbon::now(),
            ];
            if ($opId !== '') {
                $update['operator_id'] = $opId;
            }
            if (Schema::hasColumn('reports', 'callback_status') && strcasecmp((string) ($report->path ?? ''), 'Api') === 0) {
                $update['callback_status'] = 0;
            }
            $update = \Helper::realRoboIdPatch($update, $data, $report, $api);
            $columns = array_flip(Schema::getColumnListing('reports'));
            DB::table('reports')->where('id', $id)->whereIn('status', $pending)->update(array_intersect_key($update, $columns));
            if ($mapped !== 'Success') {
                self::refundReport($id, 'Status check refund');
            }
            DB::commit();
        } catch (\Throwable $e) {
            try {
                DB::rollBack();
            } catch (\Throwable $x) {
            }

            return ['ok' => false, 'id' => $id, 'message' => 'Could not save status. Wallet was not changed.'];
        }

        try {
            if ($mapped === 'Success') {
                $hasCommission = DB::table('reports')
                    ->where('parent__Id', $id)
                    ->where('transaction_type', 'Commission')
                    ->exists();
                if (! $hasCommission) {
                    \Helper::SetCommission($id);
                }
            }
            \Helper::sendApiPartnerRechargeCallback($id);
        } catch (\Throwable $e) {
        }

        return [
            'ok' => true,
            'id' => $id,
            'status' => $mapped === 'Success' ? 'Success' : 'Failed',
            'message' => $mapped === 'Success'
                ? 'Marked Success from supplier status'
                : 'Marked Failed and refunded from supplier status',
        ];
    }

    public static function payloadSaysSuccess(array $data, object $api): bool
    {
        $statusNames = [
            'status', 'txnstatus', 'transstatus', 'transactionstatus', 'rechargestatus',
            'trstatus', 'responsestatus', 'statuscode', 'resstatus',
        ];
        $messageNames = ['message', 'msg', 'remark'];
        $sawSuccess = false;
        $messageSuccess = false;
        $statusBlocks = false;
        foreach (self::statusCandidates($data) as $item) {
            $value = $item['value'];
            $word = strtoupper(trim($value));
            $isWord = in_array($word, ['SUCCESS', 'SUCCESSFUL', 'SUCCESSFULL'], true)
                || \Helper::apiValueMatches($value, $api->success_value ?? '');
            $messageHit = $isWord || (
                preg_match('/\bsuccess(?:ful|full)?\b/i', $value) === 1
                && preg_match('/\b(not|un)\s*success|\bfail|\bpending\b/i', $value) !== 1
            );
            if (in_array($item['name'], $statusNames, true)) {
                if ($isWord) {
                    $sawSuccess = true;
                } elseif (in_array($word, ['PENDING', 'PROCESS', 'PROCESSING', 'FAILED', 'FAILURE', 'FAIL', '0', '2', '3'], true) && ! $isWord) {
                    $statusBlocks = true;
                }
            } elseif (in_array($item['name'], $messageNames, true) && $messageHit) {
                $messageSuccess = true;
            }
        }

        return $sawSuccess || ($messageSuccess && ! $statusBlocks);
    }

    public static function payloadSaysFailed(array $data): bool
    {
        foreach (self::statusCandidates($data) as $item) {
            $word = strtoupper(trim($item['value']));
            if (in_array($word, ['FAILED', 'FAILURE', 'FAIL', 'REFUNDED', 'REFUND'], true)) {
                return true;
            }
        }

        return false;
    }

    private static function successFromLatestLog(object $report, ?object $api): ?array
    {
        $orderId = trim((string) ($report->order_id ?? ''));
        if ($orderId === '' || ! Schema::hasTable('apilogs') || ! $api) {
            return null;
        }
        try {
            $rows = DB::table('apilogs')
                ->where('txnid', $orderId)
                ->whereNotIn('modal', ['StatusCheck', 'MROBOTICS_SESSION', 'notification-error'])
                ->orderByDesc('id')
                ->limit(8)
                ->get(['response']);
        } catch (\Throwable $e) {
            return null;
        }
        foreach ($rows as $row) {
            $body = trim((string) ($row->response ?? ''));
            if ($body === '' || str_starts_with($body, '{"curl_error"')) {
                continue;
            }
            $payload = \Helper::supplierPayload($body);
            if ($payload === [] && preg_match('/\bSuccess(?:ful|full)?\b/i', $body) === 1 && preg_match('/\b(fail|pending)\b/i', $body) !== 1 && strlen($body) < 400 && ! str_contains($body, '<')) {
                $payload = ['status' => 'Success'];
            }
            if ($payload !== [] && self::payloadSaysSuccess($payload, $api) && ! self::payloadSaysFailed($payload)) {
                return $payload;
            }
        }

        return null;
    }

    /** @return list<array{name:string,value:string}> */
    private static function statusCandidates(array $data): array
    {
        $names = [
            'status', 'txnstatus', 'transstatus', 'transactionstatus', 'rechargestatus',
            'trstatus', 'responsestatus', 'statuscode', 'resstatus', 'message', 'msg', 'remark',
        ];
        $found = [];
        $walk = function ($node, int $depth) use (&$walk, &$found, $names): void {
            if ($depth > 4 || ! is_array($node)) {
                return;
            }
            foreach ($node as $key => $value) {
                if (is_array($value)) {
                    $walk($value, $depth + 1);
                    continue;
                }
                if (! is_scalar($value)) {
                    continue;
                }
                $name = strtolower(preg_replace('/[^a-z]/', '', (string) $key) ?? '');
                if (! in_array($name, $names, true)) {
                    continue;
                }
                $text = trim((string) $value);
                if ($text === '') {
                    continue;
                }
                $found[] = ['name' => $name, 'value' => $text];
            }
        };
        $walk($data, 0);

        return $found;
    }

    public static function refundReport(int $reportId, string $remark): bool
    {
        $report = DB::table('reports')->where('id', $reportId)->first();
        if (! $report) {
            return false;
        }

        $refundCount = DB::table('reports')
            ->where('order_id', $report->order_id)
            ->where('transaction_type', 'Refund')
            ->where('status', 'Success')
            ->count();
        $reverseCount = DB::table('reports')
            ->where('order_id', $report->order_id)
            ->where('transaction_type', 'Refund Reverse')
            ->where('status', 'Success')
            ->count();
        if ($refundCount > $reverseCount) {
            return false;
        }

        $user = DB::table('users')->where('id', $report->user_id)->lockForUpdate()->first();
        if (! $user) {
            throw new \RuntimeException('Retailer wallet was not found');
        }

        $amount = (float) $report->amount;
        $opening = (float) $user->wallet_balance;
        DB::table('users')->where('id', $user->id)->increment('wallet_balance', $amount);
        $closing = (float) DB::table('users')->where('id', $user->id)->value('wallet_balance');

        $row = [
            'user_id' => $user->id,
            'parent__Id' => $report->id,
            'order_id' => $report->order_id,
            'number' => $report->number,
            'amount' => $report->amount,
            'total_amount' => $report->total_amount,
            'admin_commission' => $report->admin_commission ?? 0,
            'api_commission' => $report->api_commission ?? 0,
            'commission' => $report->commission ?? 0,
            'fund_type' => 'Credit',
            'transaction_type' => 'Refund',
            'provider_id' => $report->provider_id,
            'service_id' => $report->service_id,
            'api_id' => $report->api_id,
            'remark' => $remark.' For Rs. '.$report->total_amount.' Number '.$report->number,
            'status' => 'Success',
            'path' => $report->path,
            'ip_address' => $report->ip_address,
            'opening_balance' => $opening,
            'closing_balance' => $closing,
            'transaction_date' => Carbon::now()->format('Y-m-d H:i:s').':'.rand(111, 999),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        $columns = array_flip(Schema::getColumnListing('reports'));
        DB::table('reports')->insert(array_intersect_key($row, $columns));

        return true;
    }

    private static function touchChecked(int $id): void
    {
        try {
            $payload = ['updated_at' => Carbon::now()];
            if (Schema::hasColumn('reports', 'status_checked_at')) {
                $payload['status_checked_at'] = Carbon::now();
            }
            DB::table('reports')->where('id', $id)->update($payload);
        } catch (\Throwable $e) {
        }
    }
}
