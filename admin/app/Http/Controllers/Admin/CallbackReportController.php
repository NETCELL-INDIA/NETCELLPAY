<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CallbackReportController extends Controller
{
    public function index()
    {
        $apis = DB::table('apis')->orderBy('api_name')->get(['id', 'api_name']);
        $defaultDate = Carbon::today()->format('Y-m-d');
        if (Schema::hasTable('apilogs')) {
            $last = DB::table('apilogs')
                ->where(function ($q) {
                    $this->applyAnyCallbackFilter($q);
                })
                ->orderByDesc('id')
                ->value('created_at');
            if ($last) {
                $defaultDate = Carbon::parse($last)->format('Y-m-d');
            }
        }

        return view('admin.recharge-reports.callback-report', compact('apis', 'defaultDate'));
    }

    public function list(Request $request)
    {
        if (! Schema::hasTable('apilogs')) {
            return response()->json([
                'type' => 'success',
                'rows' => '<tr><td colspan="10" class="text-center text-muted py-4">apilogs table not found</td></tr>',
                'pagination' => ['page' => 1, 'show' => 10, 'total' => 0, 'from' => 0, 'to' => 0, 'last_page' => 1],
                'stats' => ['received' => 0, 'sent' => 0, 'error' => 0],
            ]);
        }

        $limit = (int) ($request->show ?: 10);
        if (! in_array($limit, [10, 25, 50, 100], true)) {
            $limit = 10;
        }
        $page = max(1, (int) ($request->page ?: 1));
        $offset = ($page - 1) * $limit;

        $from = trim((string) ($request->from_date ?? ''));
        $to = trim((string) ($request->to_date ?? ''));
        if ($from === '') {
            $from = Carbon::today()->format('Y-m-d');
        }
        if ($to === '') {
            $to = $from;
        }

        $direction = strtolower(trim((string) ($request->direction ?? 'all')));
        $q = $this->baseQuery($from, $to, $direction);

        if ($request->api_id) {
            $q->where('r.api_id', (int) $request->api_id);
        }
        if ($request->filled('search')) {
            $term = trim((string) $request->search);
            $q->where(function ($w) use ($term) {
                $w->where('l.txnid', 'like', "%{$term}%")
                    ->orWhere('r.order_id', 'like', "%{$term}%")
                    ->orWhere('r.number', 'like', "%{$term}%")
                    ->orWhere('r.request_order_id', 'like', "%{$term}%")
                    ->orWhere('u.mobile_number', 'like', "%{$term}%")
                    ->orWhere('u.outlet_name', 'like', "%{$term}%");
                if (ctype_digit($term)) {
                    $w->orWhere('r.id', (int) $term)
                        ->orWhere('l.id', (int) $term);
                }
            });
        }

        $total = (clone $q)->count('l.id');
        $stats = $this->callbackStats($from, $to);

        $rowsData = (clone $q)
            ->select(
                'l.id as log_id',
                'l.modal',
                'l.url as log_url',
                'l.txnid',
                'l.request as log_request',
                'l.response as log_response',
                'l.created_at as log_at',
                'r.id as report_id',
                'r.order_id',
                'r.number',
                'r.amount',
                'r.total_amount',
                'r.status as report_status',
                'r.callback_status',
                'r.callback_response',
                'r.api_id',
                'u.outlet_name',
                'u.mobile_number',
                'p.provider_name',
                'a.api_name'
            )
            ->orderByDesc('l.id')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $rows = '';
        if ($rowsData->isEmpty()) {
            $rows = '<tr><td colspan="10" class="text-center text-muted py-4">No callback records found</td></tr>';
        } else {
            foreach ($rowsData as $row) {
                $dir = $this->directionOf($row->modal, $row->log_url);
                $dirBadge = match ($dir) {
                    'sent' => '<span class="badge bg-info">SENT</span>',
                    'error' => '<span class="badge bg-danger">ERROR</span>',
                    default => '<span class="badge bg-success">RECEIVED</span>',
                };

                // Enrich from callback JSON when apilog.txnid did not join to reports.
                $payload = $this->parsePayload($row->log_request);
                if (empty($row->report_id)) {
                    $resolved = $this->resolveReportFromPayload($payload, (string) ($row->txnid ?? ''), (string) ($row->log_url ?? ''));
                    if ($resolved) {
                        $row->report_id = $resolved->id;
                        $row->order_id = $resolved->order_id;
                        $row->number = $resolved->number;
                        $row->amount = $resolved->amount;
                        $row->total_amount = $resolved->total_amount;
                        $row->report_status = $resolved->status;
                        $row->callback_status = $resolved->callback_status ?? 0;
                        $row->api_id = $resolved->api_id;
                        $u = DB::table('users')->where('id', (int) $resolved->user_id)->first(['outlet_name', 'mobile_number']);
                        $row->outlet_name = $u->outlet_name ?? null;
                        $row->mobile_number = $u->mobile_number ?? null;
                        $p = DB::table('providers')->where('id', (int) ($resolved->provider_id ?? 0))->first(['provider_name']);
                        $row->provider_name = $p->provider_name ?? null;
                        $a = DB::table('apis')->where('id', (int) ($resolved->api_id ?? 0))->first(['api_name']);
                        $row->api_name = $a->api_name ?? null;
                    }
                }
                if (empty($row->api_name)) {
                    $apiId = $this->apiIdFromUrl((string) ($row->log_url ?? ''));
                    if ($apiId > 0) {
                        $row->api_name = DB::table('apis')->where('id', $apiId)->value('api_name') ?: null;
                    }
                }
                $orderKey = (string) ($row->order_id ?: $row->txnid ?: ($payload['refId'] ?? $payload['order_id'] ?? $payload['txnId'] ?? ''));
                $number = (string) ($row->number ?: ($payload['number'] ?? ''));
                $amount = (float) ($row->total_amount ?? $row->amount ?? ($payload['amount'] ?? 0));
                $status = (string) ($row->report_status ?: ($payload['status'] ?? '-'));

                $user = trim(($row->outlet_name ?: '').' / '.($row->mobile_number ?: ''));
                $user = ($user === '/' || trim($user, ' /') === '') ? '-' : $user;
                $order = e($orderKey !== '' ? $orderKey : '-');
                $status = e($status !== '' ? $status : '-');
                $delivery = $this->deliveryFromLog($row->log_response, $row->log_url, $dir);
                if ($dir === 'sent') {
                    $partnerSent = $delivery['ok']
                        ? '<span class="badge bg-success-subtle text-success">Delivery: OK</span>'
                        : '<span class="badge bg-danger-subtle text-danger">Delivery: Failed</span>';
                } else {
                    $partnerSent = ((int) ($row->callback_status ?? 0) === 1)
                        ? '<span class="badge bg-success-subtle text-success">Partner: Yes</span>'
                        : '<span class="badge bg-secondary-subtle text-secondary">Partner: No</span>';
                }

                $preview = $this->previewPayload(
                    $dir === 'sent'
                        ? ($row->log_response ?: $row->log_request ?: $row->log_url)
                        : ($row->log_request ?: $row->callback_response)
                );

                $rows .= '<tr>'
                    .'<td>'.e(Carbon::parse($row->log_at)->format('d-m-Y h:i:s A')).'</td>'
                    .'<td>'.$dirBadge.'</td>'
                    .'<td><strong>'.$order.'</strong>'
                    .($row->report_id ? '<br><small class="text-muted">#'.e((string) $row->report_id).'</small>' : '')
                    .'</td>'
                    .'<td>'.e($user).'</td>'
                    .'<td>'.e($row->provider_name ?: '-').'<br><small>'.e($number !== '' ? $number : '-').'</small></td>'
                    .'<td>₹'.number_format($amount, 2).'</td>'
                    .'<td>'.$status.'<br>'.$partnerSent.'</td>'
                    .'<td>'.e($row->api_name ?: '-').'<br><small class="text-muted">'.e((string) ($row->modal ?: '-')).'</small></td>'
                    .'<td><code class="small">'.e($preview).'</code></td>'
                    .'<td class="text-nowrap">'
                    .'<button type="button" class="btn btn-sm btn-outline-primary btn-cb-detail" data-log-id="'.e((string) $row->log_id).'"'
                    .($row->report_id ? ' data-report-id="'.e((string) $row->report_id).'"' : '')
                    .'>Details</button>'
                    .'</td>'
                    .'</tr>';
            }
        }

        return response()->json([
            'type' => 'success',
            'rows' => $rows,
            'stats' => $stats,
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

    public function detail(Request $request)
    {
        $logId = (int) ($request->log_id ?? 0);
        $reportId = (int) ($request->report_id ?? 0);

        $log = null;
        if ($logId > 0 && Schema::hasTable('apilogs')) {
            $log = DB::table('apilogs')->where('id', $logId)->first();
        }

        $payload = $log ? $this->parsePayload($log->request ?? '') : [];

        $report = null;
        if ($reportId > 0) {
            $report = DB::table('reports')->where('id', $reportId)->first();
        }
        if (! $report && $log) {
            $report = $this->resolveReportFromPayload($payload, (string) ($log->txnid ?? ''), (string) ($log->url ?? ''));
        }

        if (! $log && ! $report) {
            return response()->json(['type' => 'error', 'message' => 'Record not found']);
        }

        $dir = $log
            ? $this->directionOf($log->modal ?? '', $log->url ?? '')
            : 'received';

        $api = null;
        if ($report && ! empty($report->api_id)) {
            $api = DB::table('apis')->where('id', (int) $report->api_id)->first(['id', 'api_name', 'callback_switch']);
        }
        if (! $api) {
            $apiId = $this->apiIdFromUrl((string) ($log->url ?? ''));
            if ($apiId > 0) {
                $api = DB::table('apis')->where('id', $apiId)->first(['id', 'api_name', 'callback_switch']);
            }
        }
        $user = null;
        if ($report) {
            $user = DB::table('users')->where('id', (int) $report->user_id)->first(['id', 'outlet_name', 'mobile_number', 'callback_url']);
        }

        $orderDisplay = $this->firstNonEmpty([
            $log->txnid ?? '',
            $report->order_id ?? '',
            $payload['refId'] ?? '',
            $payload['order_id'] ?? '',
            $payload['orderId'] ?? '',
            $payload['txnId'] ?? '',
            $payload['request_order_id'] ?? '',
        ]);
        $numberDisplay = $this->firstNonEmpty([
            $report->number ?? '',
            $payload['number'] ?? '',
        ]);
        $amountDisplay = $report
            ? ((float) ($report->total_amount ?? $report->amount))
            : (float) ($payload['amount'] ?? 0);
        $statusDisplay = $this->firstNonEmpty([
            $report->status ?? '',
            $payload['status'] ?? '',
            $payload['Status'] ?? '',
        ]);

        $pretty = function ($raw): string {
            $raw = is_string($raw) ? $raw : json_encode($raw);
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded)) {
                return (string) json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            return (string) ($raw !== '' && $raw !== 'null' ? $raw : '-');
        };

        $row = function (string $label, string $value): string {
            return '<tr><th style="width:32%;background:#f8f9fa">'.e($label).'</th><td>'.$value.'</td></tr>';
        };

        $delivery = $log
            ? $this->deliveryFromLog($log->response ?? '', $log->url ?? '', $dir)
            : ['ok' => false, 'label' => '-', 'badge' => 'secondary', 'detail' => ''];

        $markedSent = $report && (int) ($report->callback_status ?? 0) === 1;
        if ($dir === 'sent') {
            $sentBadge = $delivery['ok']
                ? '<span class="badge bg-success">Yes — Delivered</span>'
                : '<span class="badge bg-danger">Attempted — Failed</span>';
            if ($delivery['detail'] !== '') {
                $sentBadge .= ' <small class="text-muted">'.e($delivery['detail']).'</small>';
            }
            if (! $markedSent) {
                $sentBadge .= ' <span class="badge bg-warning text-dark">flag=0</span>';
            }
        } else {
            $sentBadge = $markedSent
                ? '<span class="badge bg-success">Yes</span>'
                : '<span class="badge bg-warning text-dark">No</span>';
        }

        $dirLabel = $dir === 'sent' ? 'SENT (Partner)' : ($dir === 'error' ? 'RECEIVED ERROR' : 'RECEIVED (Supplier)');
        $html = '<div class="mb-2"><span class="badge '.($dir === 'sent' ? 'bg-info' : ($dir === 'error' ? 'bg-danger' : 'bg-success')).'">'.e($dirLabel).'</span></div>';
        $html .= '<table class="table table-bordered table-sm mb-3">'
            .$row('Log ID', e((string) ($log->id ?? '-')))
            .$row('Time', e($log ? Carbon::parse($log->created_at)->format('d-m-Y h:i:s A') : '-'))
            .$row('Modal / Type', e((string) ($log->modal ?? '-')))
            .$row('URL', '<code>'.e((string) ($log->url ?? '-')).'</code>')
            .$row('Txn / Order', e($orderDisplay !== '' ? $orderDisplay : '-'))
            .$row('Report ID', e((string) ($report->id ?? '-')))
            .$row('Number', e($numberDisplay !== '' ? $numberDisplay : '-'))
            .$row('Amount', $amountDisplay > 0 ? ('₹'.number_format($amountDisplay, 2)) : '-')
            .$row('Status', e($statusDisplay !== '' ? $statusDisplay : '-'))
            .$row('API', e((string) ($api->api_name ?? '-')))
            .$row('User', e((function () use ($user) {
                if (! $user) {
                    return '-';
                }
                $label = trim(($user->outlet_name ?? '').' / '.($user->mobile_number ?? ''));

                return ($label === '/' || trim($label, ' /') === '') ? '-' : $label;
            })()))
            .$row('Partner Callback URL', e((string) (($user && trim((string) ($user->callback_url ?? '')) !== '') ? $user->callback_url : '-')))
            .$row('Partner Callback Sent', $sentBadge)
            .$row('Delivery', '<span class="badge bg-'.e($delivery['badge']).'">'.e($delivery['label']).'</span>'
                .($delivery['detail'] !== '' ? ' <small class="text-muted">'.e($delivery['detail']).'</small>' : ''))
            .'</table>';

        if ($log) {
            $reqRaw = trim((string) ($log->request ?? ''));
            if ($reqRaw === '' || $reqRaw === '-' || $reqRaw === 'null') {
                $reqRaw = trim((string) ($log->url ?? '')) !== '' ? ('GET '.(string) $log->url) : '';
            }
            $html .= '<h6 class="text-primary">Callback Log — Request</h6>'
                .'<pre class="bg-light border rounded p-2 small mb-3" style="white-space:pre-wrap;word-break:break-word;max-height:280px;overflow:auto;">'
                .e($pretty($reqRaw)).'</pre>';
            $html .= '<h6 class="text-primary">Callback Log — Response</h6>'
                .'<pre class="bg-light border rounded p-2 small mb-3" style="white-space:pre-wrap;word-break:break-word;max-height:220px;overflow:auto;">'
                .e($pretty($log->response ?? '')).'</pre>';
        }

        if ($report) {
            $inbound = (string) ($report->callback_response ?? '');
            if ($inbound !== '' && $inbound !== 'null' && $inbound !== '[]') {
                $html .= '<h6 class="text-primary">Stored on Report — Supplier Callback</h6>'
                    .'<pre class="bg-light border rounded p-2 small mb-3" style="white-space:pre-wrap;word-break:break-word;max-height:220px;overflow:auto;">'
                    .e($pretty($inbound)).'</pre>';
            }
            $partnerUrl = Schema::hasColumn('reports', 'api_partner_call_back_url')
                ? (string) ($report->api_partner_call_back_url ?? '')
                : '';
            $partnerResp = Schema::hasColumn('reports', 'api_partner_callback_response')
                ? (string) ($report->api_partner_callback_response ?? '')
                : '';
            if ($partnerUrl === '' && $log && $dir === 'sent') {
                $partnerUrl = (string) ($log->url ?? '');
            }
            if (($partnerResp === '' || $partnerResp === '-' || $partnerResp === 'null') && $log && $dir === 'sent') {
                $partnerResp = (string) ($log->response ?? '');
            }
            if ($partnerUrl !== '' || $partnerResp !== '') {
                $html .= '<h6 class="text-primary">Stored on Report — Partner Callback Sent</h6>'
                    .'<div class="small text-muted">URL</div>'
                    .'<pre class="bg-light border rounded p-2 small mb-2" style="white-space:pre-wrap;word-break:break-word;">'
                    .e($partnerUrl !== '' ? $partnerUrl : '-').'</pre>'
                    .'<div class="small text-muted">Response</div>'
                    .'<pre class="bg-light border rounded p-2 small mb-0" style="white-space:pre-wrap;word-break:break-word;max-height:200px;overflow:auto;">'
                    .e($pretty($partnerResp)).'</pre>';
            }
        }

        return response()->json([
            'type' => 'success',
            'html' => $html,
            'direction' => $dir,
            'report_id' => $report->id ?? null,
        ]);
    }


    private function baseQuery(string $from, string $to, string $direction)
    {
        $q = DB::table('apilogs as l')
            ->leftJoin('reports as r', function ($join) {
                $join->whereRaw('r.order_id COLLATE utf8mb4_unicode_ci = l.txnid COLLATE utf8mb4_unicode_ci');
            })
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->leftJoin('providers as p', 'p.id', '=', 'r.provider_id')
            ->leftJoin('apis as a', 'a.id', '=', 'r.api_id')
            ->whereBetween('l.created_at', [$from.' 00:00:00', $to.' 23:59:59']);

        if ($direction === 'received') {
            $q->where(function ($w) {
                $this->applyReceivedFilter($w, 'l');
            });
        } elseif ($direction === 'sent') {
            $q->where(function ($w) {
                $this->applySentFilter($w, 'l');
            });
        } elseif ($direction === 'error') {
            $q->where(function ($w) {
                $this->applyErrorFilter($w, 'l');
            });
        } else {
            $q->where(function ($w) {
                $this->applyAnyCallbackFilter($w, 'l');
            });
        }

        return $q;
    }

    private function applyAnyCallbackFilter($q, string $alias = ''): void
    {
        $col = $alias !== '' ? $alias.'.modal' : 'modal';
        $url = $alias !== '' ? $alias.'.url' : 'url';
        $q->where(function ($w) use ($col, $url) {
            $w->whereIn($col, ['RechargeCallback', 'RechargeCallbackError', 'USER_RECHARGE_CALLBACK'])
                ->orWhere($col, 'like', '%CALLBACK%')
                ->orWhere($url, 'like', '%recharge-callback%');
        });
    }

    private function applyReceivedFilter($q, string $alias = ''): void
    {
        $col = $alias !== '' ? $alias.'.modal' : 'modal';
        $url = $alias !== '' ? $alias.'.url' : 'url';
        $q->where(function ($w) use ($col, $url) {
            $w->whereIn($col, ['RechargeCallback', 'RechargeCallbackError'])
                ->orWhere($url, 'like', '%/recharge-callback/%')
                ->orWhere($url, 'like', '%recharge-callback-error%');
        });
    }

    private function applySentFilter($q, string $alias = ''): void
    {
        $col = $alias !== '' ? $alias.'.modal' : 'modal';
        $q->where(function ($w) use ($col) {
            $w->where($col, 'USER_RECHARGE_CALLBACK')
                ->orWhere($col, 'like', 'USER_%CALLBACK%');
        });
    }

    private function applyErrorFilter($q, string $alias = ''): void
    {
        $col = $alias !== '' ? $alias.'.modal' : 'modal';
        $url = $alias !== '' ? $alias.'.url' : 'url';
        $q->where(function ($w) use ($col, $url) {
            $w->where($col, 'RechargeCallbackError')
                ->orWhere($url, 'like', '%recharge-callback-error%');
        });
    }

    private function directionOf(?string $modal, ?string $url): string
    {
        $modal = (string) $modal;
        $url = (string) $url;
        if (stripos($modal, 'USER_') === 0 && stripos($modal, 'CALLBACK') !== false) {
            return 'sent';
        }
        if ($modal === 'RechargeCallbackError' || stripos($url, 'recharge-callback-error') !== false) {
            return 'error';
        }

        return 'received';
    }

    private function previewPayload(?string $raw): string
    {
        $raw = trim((string) $raw);
        if ($raw === '' || $raw === 'null') {
            return '-';
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $bits = [];
            foreach (['curl_error', 'http_code', 'status', 'Status', 'STATUS', 'message', 'Message', 'refId', 'order_id', 'txnId', 'number', 'amount', 'request_order_id', 'yourRequestId', 'AGENTID'] as $k) {
                if (isset($decoded[$k]) && ! is_array($decoded[$k])) {
                    $bits[] = $k.'='.$decoded[$k];
                }
            }
            if ($bits !== []) {
                return mb_substr(implode(' · ', $bits), 0, 80);
            }
        }

        return mb_substr(preg_replace('/\s+/', ' ', $raw) ?? $raw, 0, 80);
    }

    /**
     * @return array{ok:bool,label:string,badge:string,detail:string}
     */
    private function deliveryFromLog(?string $response, ?string $url, string $dir): array
    {
        if ($dir !== 'sent' && $dir !== 'error') {
            return ['ok' => true, 'label' => 'Received', 'badge' => 'success', 'detail' => ''];
        }

        $raw = trim((string) $response);
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && (isset($decoded['curl_error']) || (isset($decoded['http_code']) && (int) $decoded['http_code'] === 0))) {
            $detail = (string) ($decoded['curl_error'] ?? ('HTTP '.(int) ($decoded['http_code'] ?? 0)));

            return ['ok' => false, 'label' => 'Failed', 'badge' => 'danger', 'detail' => $detail];
        }

        if ($raw === '' || $raw === '-' || $raw === 'null') {
            return ['ok' => false, 'label' => 'No response', 'badge' => 'warning', 'detail' => ''];
        }

        if (is_array($decoded) && isset($decoded['http_code'])) {
            $code = (int) $decoded['http_code'];
            if ($code >= 200 && $code < 300) {
                return ['ok' => true, 'label' => 'OK', 'badge' => 'success', 'detail' => 'HTTP '.$code];
            }
            if ($code > 0) {
                return ['ok' => false, 'label' => 'HTTP '.$code, 'badge' => 'danger', 'detail' => ''];
            }
        }

        // Non-JSON body with content usually means partner accepted the GET
        if ($raw !== '' && ! str_contains(strtolower($raw), 'curl_error')) {
            return ['ok' => true, 'label' => 'OK', 'badge' => 'success', 'detail' => ''];
        }

        return ['ok' => false, 'label' => 'Failed', 'badge' => 'danger', 'detail' => mb_substr($raw, 0, 120)];
    }

    private function callbackStats(string $from, string $to): array
    {
        $received = DB::table('apilogs')
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->where(function ($w) {
                $this->applyReceivedFilter($w);
            })
            ->count();

        $sent = DB::table('apilogs')
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->where(function ($w) {
                $this->applySentFilter($w);
            })
            ->count();

        $error = DB::table('apilogs')
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->where(function ($w) {
                $this->applyErrorFilter($w);
            })
            ->count();

        return [
            'received' => $received,
            'sent' => $sent,
            'error' => $error,
        ];
    }

    private function parsePayload($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        $raw = trim((string) $raw);
        if ($raw === '' || $raw === '-' || $raw === 'null') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }
        foreach (['data', 'Data', 'payload', 'result', 'Result'] as $wrap) {
            if (isset($decoded[$wrap]) && is_array($decoded[$wrap])) {
                $decoded = array_merge($decoded, $decoded[$wrap]);
            }
        }

        return $decoded;
    }

    private function firstNonEmpty(array $candidates): string
    {
        foreach ($candidates as $c) {
            $v = trim((string) $c);
            if ($v !== '' && strcasecmp($v, 'null') !== 0 && $v !== '-') {
                return $v;
            }
        }

        return '';
    }

    private function apiIdFromUrl(string $url): int
    {
        if (preg_match('#/recharge-callback(?:-error)?/(\d+)#i', $url, $m)) {
            return (int) $m[1];
        }

        return 0;
    }

    private function resolveReportFromPayload(array $payload, string $txnid = '', string $url = ''): ?object
    {
        $refs = [];
        foreach ([
            $txnid,
            $payload['refId'] ?? '',
            $payload['RefId'] ?? '',
            $payload['ref_id'] ?? '',
            $payload['order_id'] ?? '',
            $payload['orderId'] ?? '',
            $payload['OrderId'] ?? '',
            $payload['request_order_id'] ?? '',
            $payload['requestOrderId'] ?? '',
            $payload['txnId'] ?? '',
            $payload['txnid'] ?? '',
            $payload['transactionId'] ?? '',
        ] as $ref) {
            $ref = trim((string) $ref);
            if ($ref !== '' && strcasecmp($ref, 'null') !== 0) {
                $refs[] = $ref;
            }
        }
        $refs = array_values(array_unique($refs));
        $apiId = $this->apiIdFromUrl($url);

        foreach ($refs as $ref) {
            $q = DB::table('reports')->where(function ($w) use ($ref) {
                $w->where('order_id', $ref);
                if (Schema::hasColumn('reports', 'request_order_id')) {
                    $w->orWhere('request_order_id', $ref);
                }
                if (Schema::hasColumn('reports', 'api_operator_id')) {
                    $w->orWhere('api_operator_id', $ref);
                }
                $w->orWhere('operator_id', $ref);
            })->whereIn('transaction_type', ['Recharge', 'Bill Pay', 'Bill Payment']);
            if ($apiId > 0) {
                $hit = (clone $q)->where('api_id', $apiId)->orderByDesc('id')->first();
                if ($hit) {
                    return $hit;
                }
            }
            $hit = $q->orderByDesc('id')->first();
            if ($hit) {
                return $hit;
            }
        }

        $number = trim((string) ($payload['number'] ?? ''));
        $amount = $payload['amount'] ?? null;
        if ($number !== '' && $amount !== null && $amount !== '') {
            $q = DB::table('reports')
                ->where('number', $number)
                ->where(function ($w) use ($amount) {
                    $w->where('total_amount', $amount)->orWhere('amount', $amount);
                })
                ->whereIn('transaction_type', ['Recharge', 'Bill Pay', 'Bill Payment'])
                ->orderByDesc('id');
            if ($apiId > 0) {
                $hit = (clone $q)->where('api_id', $apiId)->first();
                if ($hit) {
                    return $hit;
                }
            }

            return $q->first();
        }

        return null;
    }
}
