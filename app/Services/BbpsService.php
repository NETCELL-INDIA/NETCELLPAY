<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BbpsService
{
    public static function resolveApiForProvider(int $providerId): ?object
    {
        $provider = DB::table('providers')->where('id', $providerId)->first();
        if (!$provider) {
            return null;
        }

        $apiId = (int) ($provider->api_id ?: config('recharge_services.plan_api_id', 7));
        $api = DB::table('apis')->where('id', $apiId)->where('status', '1')->first();
        if (!$api) {
            $api = DB::table('apis')->where('id', config('recharge_services.plan_api_id', 7))->first();
        }

        return $api;
    }

    public static function fetchBill(int $providerId, array $fields): array
    {
        $provider = DB::table('providers')->where('id', $providerId)->where('status', 1)->first();
        if (!$provider) {
            return ['type' => 'error', 'message' => 'Provider not found or inactive.'];
        }

        $api = self::resolveApiForProvider($providerId);
        if (!$api || empty($api->api_username) || empty($api->api_password)) {
            return ['type' => 'error', 'message' => 'Bill fetch API not configured. Contact admin.'];
        }

        $apiId = (int) ($provider->api_id ?: config('recharge_services.plan_api_id', 7));
        $operatorCode = \helpers::ApiProviderCode($apiId, $providerId);
        if (!$operatorCode) {
            return ['type' => 'error', 'message' => 'Operator code not mapped for this biller.'];
        }

        $accountNo = '';
        $optionals = [];
        foreach ($fields as $key => $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }
            if ($accountNo === '' && (str_starts_with($key, 'filed_') || $key === 'account' || $key === 'number')) {
                $accountNo = $value;
                continue;
            }
            if (str_starts_with($key, 'filed_')) {
                $optionals[] = $value;
            }
        }

        if ($accountNo === '') {
            return ['type' => 'error', 'message' => 'Customer account / number is required.'];
        }

        $base = rtrim(str_replace('/api/', '/Api/', $api->api_url), '/');
        if (!str_contains($base, 'planapi')) {
            $base = 'https://planapi.in/Api';
        }

        $url = $base . '/Mobile/BBPSBillInfo?apimember_id=' . urlencode($api->api_username)
            . '&api_password=' . urlencode($api->api_password)
            . '&operator_code=' . urlencode($operatorCode)
            . '&Accountno=' . urlencode($accountNo);

        foreach ($optionals as $idx => $opt) {
            $url .= '&Optional' . ($idx + 1) . '=' . urlencode($opt);
        }

        $orderId = 'BF' . date('YmdHis') . rand(11111, 999999);
        $result = \helpers::curl($url, 'GET', '', [], 'yes', 'BILL_INFO', $orderId);

        if (empty($result['response'])) {
            return ['type' => 'error', 'message' => 'Unable to fetch bill. Please try again.'];
        }

        $data = json_decode($result['response'], true);
        if (!is_array($data)) {
            return ['type' => 'error', 'message' => 'Invalid response from bill fetch API.'];
        }

        $amount = self::pickAmount($data);
        if ($amount <= 0) {
            $message = $data['Message'] ?? $data['message'] ?? $data['error'] ?? 'Bill amount not available.';
            return ['type' => 'error', 'message' => (string) $message];
        }

        return [
            'type' => 'success',
            'message' => 'Bill fetched successfully.',
            'data' => [
                'amount' => $amount,
                'number' => $accountNo,
                'customer_name' => $data['CustomerName'] ?? $data['customer_name'] ?? $data['Name'] ?? '',
                'bill_date' => $data['BillDate'] ?? $data['bill_date'] ?? '',
                'due_date' => $data['DueDate'] ?? $data['due_date'] ?? '',
                'bill_number' => $data['BillNumber'] ?? $data['bill_number'] ?? '',
                'raw' => $data,
            ],
        ];
    }

    private static function pickAmount(array $data): float
    {
        foreach (['Amount', 'amount', 'BillAmount', 'bill_amount', 'DueAmount', 'due_amount', 'NetAmount', 'net_amount'] as $key) {
            if (isset($data[$key]) && is_numeric($data[$key]) && (float) $data[$key] > 0) {
                return (float) $data[$key];
            }
        }

        if (isset($data['records']) && is_array($data['records'])) {
            foreach ($data['records'] as $row) {
                if (is_array($row)) {
                    $amt = self::pickAmount($row);
                    if ($amt > 0) {
                        return $amt;
                    }
                }
            }
        }

        return 0;
    }

    public static function servicesWithProviders(): array
    {
        $catalog = config('recharge_services', []);
        $out = ['recharge' => [], 'bbps' => []];

        foreach ($catalog['recharge'] ?? [] as $svc) {
            $providers = DB::table('providers')
                ->where('service_id', $svc['id'])
                ->where('status', 1)
                ->where('deleted_at', 0)
                ->orderBy('provider_name')
                ->get(['id', 'provider_name', 'provider_logo', 'service_id']);

            $out['recharge'][] = array_merge($svc, [
                'providers' => $providers,
                'provider_count' => $providers->count(),
            ]);
        }

        foreach ($catalog['bbps'] ?? [] as $svc) {
            $providers = DB::table('providers')
                ->where('service_id', $svc['id'])
                ->where('status', 1)
                ->where('deleted_at', 0)
                ->orderBy('provider_name')
                ->get(['id', 'provider_name', 'provider_logo', 'service_id']);

            $out['bbps'][] = array_merge($svc, [
                'providers' => $providers,
                'provider_count' => $providers->count(),
            ]);
        }

        return $out;
    }
}
