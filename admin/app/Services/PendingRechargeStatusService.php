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
        $statusUrl = trim((string) ($api->status_check_url ?? ''));
        if (! $api || $statusUrl === '') {
            self::touchChecked($id);

            return ['ok' => false, 'id' => $id, 'message' => 'Status check URL is not set for this API'];
        }
        if (trim((string) ($api->api_url ?? '')) !== '' && $statusUrl === trim((string) $api->api_url)) {
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
        if ($body === '' || ! empty($result['error'])) {
            return ['ok' => false, 'id' => $id, 'message' => 'Status API did not respond. Order left pending.'];
        }

        $data = json_decode($body, true);
        if (! is_array($data)) {
            return ['ok' => false, 'id' => $id, 'message' => 'Status response was not JSON. Order left pending.'];
        }

        $actual = \Helper::apiArrayGet($data, $api->status_value ?? '');
        if ($actual === null && isset($data['Response'])) {
            $actual = $data['Response'];
        }
        $mapped = \Helper::mapApiLiveStatus($api, $actual, $data);
        if ($mapped === null || $mapped === 'Pending') {
            return ['ok' => true, 'id' => $id, 'status' => 'Pending', 'message' => 'Supplier still shows pending'];
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
            $msg = trim((string) ($data['Message'] ?? $data['message'] ?? ''));
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
