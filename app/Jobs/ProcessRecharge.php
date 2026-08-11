<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessRecharge implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Retry attempts and backoff policy: job will be retried up to 3 times with increasing backoff
    public $tries = 3;
    public $backoff = [30, 60, 120];

    public $api_id;
    public $provider_id;
    public $report_id;
    public $service;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($api_id, $provider_id, $report_id, $service = 'Recharge')
    {
        $this->api_id = $api_id;
        $this->provider_id = $provider_id;
        $this->report_id = $report_id;
        $this->service = $service;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        @set_time_limit(120);
        @ignore_user_abort(true);

        try {
            $report = DB::table('reports')->where('id', $this->report_id)->first();
            if (!$report) {
                return;
            }

            // Only one worker/request may claim a Pending report (prevents duplicate netcell.in hits).
            if ($report->status !== 'Pending') {
                \helpers::logRechargeTiming([
                    'phase' => 'process_recharge_skipped',
                    'order_ref' => \helpers::maskOrderId($report->order_id ?? null),
                    'report_id' => $this->report_id,
                    'provider_id' => (int) $this->provider_id,
                    'api_id' => (int) $this->api_id,
                    'service' => $this->service,
                    'result' => ['status' => $report->status, 'message' => 'not_pending'],
                ]);
                return;
            }

            // If this request has a request_order_id, see if another report with same request_order_id is already completed
            if (!empty($report->request_order_id)) {
                $existing = DB::table('reports')
                    ->where('request_order_id', $report->request_order_id)
                    ->where('id', '!=', $report->id)
                    ->whereIn('status', ['Success', 'Failed', 'Refunded', 'Transferred'])
                    ->first();

                if ($existing) {
                    DB::table('reports')->where('id', $report->id)->update([
                        'status' => $existing->status,
                        'operator_id' => $existing->operator_id,
                        'api_operator_id' => $existing->api_operator_id ?? '',
                        'remark' => $existing->remark,
                        'callback_status' => $existing->callback_status ?? 1,
                        'updated_at' => Carbon::now(),
                    ]);
                    return;
                }
            }

            $claimed = DB::table('reports')
                ->where('id', $report->id)
                ->where('status', 'Pending')
                ->update([
                    'status' => 'Processing',
                    'updated_at' => Carbon::now(),
                ]);

            if (!$claimed) {
                \helpers::logRechargeTiming([
                    'phase' => 'process_recharge_skipped',
                    'order_ref' => \helpers::maskOrderId($report->order_id ?? null),
                    'report_id' => $this->report_id,
                    'provider_id' => (int) $this->provider_id,
                    'api_id' => (int) $this->api_id,
                    'service' => $this->service,
                    'result' => ['status' => 'error', 'message' => 'claim_failed'],
                ]);
                return;
            }

            $report = DB::table('reports')->where('id', $this->report_id)->first();

            $provider = DB::table('providers')->where('id', $this->provider_id)->first();
            if (!$provider) {
                DB::table('reports')->where('id', $this->report_id)->where('status', 'Processing')->update([
                    'status' => 'Pending',
                    'remark' => 'Recharge provider not found',
                    'updated_at' => Carbon::now(),
                ]);
                \helpers::logRechargeTiming([
                    'phase' => 'process_recharge_skipped',
                    'order_ref' => \helpers::maskOrderId($report->order_id ?? null),
                    'report_id' => $this->report_id,
                    'provider_id' => (int) $this->provider_id,
                    'api_id' => (int) $this->api_id,
                    'service' => $this->service,
                    'result' => ['status' => 'error', 'message' => 'provider_not_found'],
                ]);
                return;
            }

            $api_ids_to_try = [];
            $api_ids_to_try[] = $this->api_id;
            if ($provider->backup_api_id) $api_ids_to_try[] = $provider->backup_api_id;
            if ($provider->backup_api2_id) $api_ids_to_try[] = $provider->backup_api2_id;
            if ($provider->backup_api3_id) $api_ids_to_try[] = $provider->backup_api3_id;

            $final_result = null;
            $api_called = false;
            foreach ($api_ids_to_try as $try_api_id) {
                $apiStarted = microtime(true);
                $api_details = DB::table('apis')->where('id', $try_api_id)->first();
                if (!$api_details || $api_details->status != '1') {
                    \helpers::logRechargeTiming([
                        'phase' => 'process_recharge_skipped',
                        'order_ref' => \helpers::maskOrderId($report->order_id ?? null),
                        'report_id' => $this->report_id,
                        'provider_id' => (int) $this->provider_id,
                        'api_id' => (int) $try_api_id,
                        'service' => $this->service,
                        'result' => ['status' => 'error', 'message' => 'api_inactive_or_missing'],
                    ]);
                    continue;
                }

                $provider_code = \helpers::ApiProviderCode($try_api_id, $this->provider_id);
                $state_code = \helpers::ApiStateCode($try_api_id, $report->state_id);

                $url = $api_details->api_url;
                $url = str_replace('{API_USERNAME}', '' . $api_details->api_username . '', $url);
                $url = str_replace('{API_PASSWORD}', '' . $api_details->api_password . '', $url);
                $url = str_replace('{API_KEY}', '' . $api_details->api_key . '', $url);
                $url = str_replace('{NUMBER}', '' . $report->number . '', $url);
                $url = str_replace('{PROVIDER_CODE}', '' . $provider_code . '', $url);
                $url = str_replace('{STATE_CODE}', '' . $state_code . '', $url);
                $url = str_replace('{AMOUNT}', '' . $report->total_amount . '', $url);
                $url = str_replace('{ORDER_ID}', '' . $report->order_id . '', $url);

                $method = strtoupper(trim($api_details->api_method ?: 'GET'));
                $header = [];
                $parameters = "";

                // Query-string URLs (e.g. netcell.in transaction-request) must use GET when no POST body is set.
                if ($method === 'POST' && $parameters === '' && str_contains($url, '?')) {
                    $method = 'GET';
                }

                $result = \helpers::curl($url, $method, $parameters, $header, "yes", "Recharge", $report->order_id);
                $api_called = true;
                $apiDurationMs = (int) round((microtime(true) - $apiStarted) * 1000);

                \helpers::logRechargeTiming([
                    'phase' => 'external_api_call',
                    'order_ref' => \helpers::maskOrderId($report->order_id ?? null),
                    'report_id' => $this->report_id,
                    'provider_id' => (int) $this->provider_id,
                    'api_id' => (int) $try_api_id,
                    'service' => $this->service,
                    'api_host' => parse_url($url, PHP_URL_HOST) ?: 'unknown',
                    'api_http_code' => $result['code'] ?? null,
                    'api_ms' => $apiDurationMs,
                    'curl_error' => !empty($result['error']),
                    'result' => [
                        'parsed_status' => null,
                    ],
                ]);

// If curl returned an error or no response or server error, treat as transient and allow job retry/backoff
if ((empty($result['response']) && !empty($result['error'])) || empty($result['response']) || (isset($result['code']) && $result['code'] >= 500)) {
    // throw exception so the job retries according to $tries and $backoff
    throw new \Exception('Transient API error: ' . ($result['error'] ?? 'empty response'));
}

$update = [
    'status' => 'Pending',
    'operator_id' => '',
    'api_operator_id' => '',
    'remark' => $this->service . ' Pending For Rs. ' . $report->total_amount . ' Number ' . $report->number,
    'api_partner_order_id' => $report->api_partner_order_id,
    'order_id' => $report->order_id,
];

if ($result && isset($result['response']) && $api_details->api_format == 'JSON') {
    $data = json_decode($result['response'], true);
    if (!is_array($data)) {
        $update['status'] = 'Pending';
    } else {
    $status_key = $api_details->status_value;
    $error_key = $api_details->error_value;

    // netcell.in may return Response=Success alongside Status=Success
    if ($status_key && !isset($data[$status_key]) && isset($data['Response']) && $data['Response'] === ($api_details->success_value ?: 'Success')) {
        $data[$status_key] = $api_details->success_value ?: 'Success';
    }

                    if (isset($data[$status_key])) {
                        if ($data[$status_key] == $api_details->success_value) {
                            $update['status'] = 'Success';
                            $update['operator_id'] = $data[$api_details->operator_id_value] ?? '';
                            $update['api_operator_id'] = $data[$api_details->order_id_value] ?? '';
                            $msg = $data['Message'] ?? $data['message'] ?? '';
                            $update['remark'] = trim($this->service . ' Successful For Rs. ' . $report->total_amount . ' Number ' . $report->number . ($msg ? ' - ' . $msg : ''));
                        } else if ($data[$status_key] == $api_details->failed_value || $data[$status_key] == $api_details->refund_value) {
                            $update['status'] = 'Failed';
                            $update['operator_id'] = $data[$api_details->operator_id_value] ?? '';
                            $update['api_operator_id'] = $data[$api_details->order_id_value] ?? '';
                            $msg = $data['Message'] ?? $data['message'] ?? '';
                            $update['remark'] = trim($this->service . ' Failed For Rs. ' . $report->total_amount . ' Number ' . $report->number . ($msg ? ' - ' . $msg : ''));
                        } else if (isset($data[$error_key]) && $data[$error_key] == $api_details->error_value_response) {
                            $update['status'] = 'Failed';
                            $update['operator_id'] = '';
                            $update['api_operator_id'] = '';
                            $update['remark'] = $this->service . ' Failed For Rs. ' . $report->total_amount . ' Number ' . $report->number;
                        } else {
                            $update['status'] = 'Pending';
                        }
                    } else {
                        $update['status'] = 'Pending';
                    }
    }
                } else {
                    // keep pending
                }

                // Update api_id on reports (important when trying backups)
                DB::table('reports')->where('id', $this->report_id)->update(array_merge(['api_id' => $try_api_id], $update));

                \helpers::logRechargeTiming([
                    'phase' => 'external_api_result',
                    'order_ref' => \helpers::maskOrderId($report->order_id ?? null),
                    'report_id' => $this->report_id,
                    'provider_id' => (int) $this->provider_id,
                    'api_id' => (int) $try_api_id,
                    'service' => $this->service,
                    'api_ms' => $apiDurationMs,
                    'result' => [
                        'status' => $update['status'] ?? 'Unknown',
                    ],
                ]);

                if ($update['status'] == 'Success') {
                    // set commission and stop
                    \helpers::SetCommission($this->report_id);

        // Send SMS notification (insert into messages table for cron/sms worker)
        try {
            $reportRow = DB::table('reports')->where('id', $this->report_id)->first();
            $user = DB::table('users')->where('id', $reportRow->user_id)->first();
            $content = 'Recharge successful for Rs. ' . $reportRow->total_amount . '. Order ID: ' . $reportRow->order_id;
            DB::table('messages')->insert([
                'user_id' => 1,
                'to_user_id' => $user->id,
                'subject' => 'recharge_success',
                'msg_source' => 'SMS',
                'template_id' => 0,
                'content' => $content,
                'status' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            // Send FCM push if token exists
            $token = $user->fcm_token ?? ($user->device_token ?? null);
            if ($token) {
                \helpers::sendFcmNotification($token, 'Recharge Successful', $content, ['order_id' => $reportRow->order_id, 'amount' => $reportRow->total_amount]);
            }
        } catch (\Throwable $e) {
            // ignore notification errors but log
            DB::table('apilogs')->insert([
                'url' => 'notification-error',
                'modal' => 'ProcessRecharge',
                'txnid' => $this->report_id,
                'header' => json_encode([]),
                'request' => json_encode(['error' => $e->getMessage()]),
                'response' => 'notification failed',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        $final_result = $update;
        break;
    } elseif ($update['status'] == 'Failed') {
        // try next backup (loop continues) or if last, retry later or refund
        $final_result = $update;
        // continue to next api in list
    } else {
        // pending: stop here and let callbacks resolve later
        $final_result = $update;
        break;
    }
}

            if (!$api_called) {
                DB::table('reports')->where('id', $this->report_id)->where('status', 'Processing')->update([
                    'status' => 'Pending',
                    'remark' => 'Recharge API unavailable — check admin API settings',
                    'updated_at' => Carbon::now(),
                ]);
                \helpers::logRechargeTiming([
                    'phase' => 'process_recharge_skipped',
                    'order_ref' => \helpers::maskOrderId($report->order_id ?? null),
                    'report_id' => $this->report_id,
                    'provider_id' => (int) $this->provider_id,
                    'api_id' => (int) $this->api_id,
                    'service' => $this->service,
                    'result' => ['status' => 'error', 'message' => 'no_active_api'],
                ]);
            }

// If final_result is Failed after all attempts, schedule a delayed retry or refund
if ($final_result && $final_result['status'] == 'Failed') {
    try {
        $reportRow = DB::table('reports')->where('id', $this->report_id)->first();
        $retryCount = $reportRow->retry_count ?? 0;
        $maxRetries = 1; // one auto-retry after 5 minutes
        if ($retryCount < $maxRetries) {
            DB::table('reports')->where('id', $this->report_id)->update([
                'retry_count' => $retryCount + 1,
                'status' => 'Pending',
                'updated_at' => Carbon::now(),
            ]);
            \App\Jobs\ProcessRecharge::dispatch($this->api_id, $this->provider_id, $this->report_id, $this->service)->delay(now()->addMinutes(5));
        } else {
            // already retried, perform refund
            \helpers::refund_row($this->report_id);
        }
    } catch (\Throwable $e) {
        // on error just log and refund as safe fallback
        DB::table('apilogs')->insert([
            'url' => 'retry-schedule-error',
            'modal' => 'ProcessRecharge',
            'txnid' => $this->report_id,
            'header' => json_encode([]),
            'request' => json_encode(['error' => $e->getMessage()]),
            'response' => 'retry scheduling failed',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        \helpers::refund_row($this->report_id);
    }
}

        } catch (\Throwable $th) {
            try {
                DB::table('reports')
                    ->where('id', $this->report_id)
                    ->where('status', 'Processing')
                    ->update(['status' => 'Pending', 'updated_at' => Carbon::now()]);
            } catch (\Throwable $ignored) {
            }

            DB::table('apilogs')->insert([
                'url' => 'JobProcessRecharge',
                'modal' => 'ProcessRecharge',
                'txnid' => DB::table('reports')->where('id', $this->report_id)->value('order_id') ?: (string) $this->report_id,
                'header' => json_encode([]),
                'request' => json_encode(['api_id'=>$this->api_id,'provider_id'=>$this->provider_id]),
                'response' => $th->getMessage(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
    // rethrow to allow queue worker to retry according to $tries and $backoff
    throw $th;
}
    }
}
