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

    public static function checkDue(int $limit = 15): array
    {
        self::ensureColumns();
        $pending = ['Pending', 'Under Process', 'Under Proces', 'Processing'];
        $ids = DB::table('reports')
            ->whereIn('transaction_type', ['Recharge', 'Bill Pay'])
            ->whereIn('status', $pending)
            ->where('created_at', '<=', Carbon::now()->subMinutes(2))
            ->where('created_at', '>=', Carbon::now()->subHours(48))
            ->where('api_id', '>', 0)
            ->where(function ($q) {
                $q->whereNull('status_checked_at')
                    ->orWhere('status_checked_at', '<=', Carbon::now()->subMinutes(3));
            })
            ->orderBy('id')
            ->limit(max(1, min($limit, 30)))
            ->pluck('id');

        $out = [];
        foreach ($ids as $id) {
            $out[] = self::checkOne((int) $id);
        }

        return $out;
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
            $providerCode = (string) \helpers::ApiProviderCode((int) $api->id, (int) $report->provider_id);
            $stateCode = (string) \helpers::ApiStateCode((int) $api->id, (int) ($report->state_id ?? 0));
        } catch (\Throwable $e) {
        }

        $url = \helpers::applyApiUrlPlaceholders($statusUrl, $api, $report, $providerCode, $stateCode);
        $rechargeUrl = \helpers::applyApiUrlPlaceholders((string) ($api->api_url ?? ''), $api, $report, $providerCode, $stateCode);
        if ($rechargeUrl !== '' && $url === $rechargeUrl) {
            self::touchChecked($id);

            return ['ok' => false, 'id' => $id, 'message' => 'Status URL matches the recharge URL, so it was not called'];
        }

        $method = strtoupper(trim((string) ($api->status_check_method ?? 'GET')));
        if (! in_array($method, ['GET', 'POST'], true)) {
            $method = 'GET';
        }

        self::touchChecked($id);
        $result = \helpers::curl($url, $method, '', [], 'yes', 'StatusCheck', (string) ($report->order_id ?: $id));
        $body = (string) ($result['response'] ?? '');
        if ($body === '' || ! empty($result['error'])) {
            return ['ok' => false, 'id' => $id, 'message' => 'Status API did not respond. Order left pending.'];
        }

        $data = json_decode($body, true);
        if (! is_array($data)) {
            return ['ok' => false, 'id' => $id, 'message' => 'Status response was not JSON. Order left pending.'];
        }

        $actual = \helpers::apiArrayGet($data, $api->status_value ?? '');
        if ($actual === null && isset($data['Response'])) {
            $actual = $data['Response'];
        }
        $mapped = \helpers::mapApiLiveStatus($api, $actual, $data);
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

            $opId = \helpers::sanitizeOperatorId(\helpers::apiArrayGet($data, $api->operator_id_value ?? '') ?? '');
            $msg = trim((string) ($data['Message'] ?? $data['message'] ?? ''));
            $update = [
                'status' => $mapped === 'Success' ? 'Success' : 'Failed',
                'remark' => 'Status check: '.$mapped.($msg !== '' ? ' - '.$msg : ''),
                'updated_at' => Carbon::now(),
                'status_checked_at' => Carbon::now(),
            ];
            if ($opId !== '') {
                $update['operator_id'] = $opId;
            }
            if (\helpers::isApiPartnerPath($report->path ?? '') && Schema::hasColumn('reports', 'callback_status')) {
                $update['callback_status'] = 0;
            }
            DB::table('reports')->where('id', $id)->whereIn('status', $pending)->update(
                \helpers::safeReportApiUpdate($update)
            );
            if ($mapped !== 'Success') {
                \helpers::refund_row($id);
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
                    \helpers::SetCommission($id);
                }
            } else {
                \helpers::closeOpenComplaintsForReport($id, 'Recharge failed on status check.');
            }
            \helpers::sendApiPartnerRechargeCallback($id);
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

    private static function touchChecked(int $id): void
    {
        try {
            DB::table('reports')->where('id', $id)->update([
                'status_checked_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
        }
    }
}
