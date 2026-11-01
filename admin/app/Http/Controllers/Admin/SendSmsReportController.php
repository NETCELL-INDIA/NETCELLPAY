<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SendSmsReportController extends Controller
{
    public function index()
    {
        return view('admin.extras.send-sms-report');
    }

    public function list(Request $request)
    {
        if (!Schema::hasTable('apilogs')) {
            return $this->emptyResponse();
        }

        $limit = (int) ($request->show ?: 10);
        if (!in_array($limit, [10, 25, 50, 100], true)) {
            $limit = 10;
        }

        $page = max(1, (int) ($request->page ?: 1));
        $offset = ($page - 1) * $limit;
        $mobile = trim((string) $request->mobile);

        $q = DB::table('apilogs')
            ->whereIn('modal', ['MESSAGE', 'WHATSAPP_URL', 'SMS']);

        if ($mobile !== '') {
            $q->where(function ($w) use ($mobile) {
                $w->where('url', 'like', '%' . $mobile . '%')
                    ->orWhere('txnid', 'like', '%' . $mobile . '%');
            });
        }

        $total = (clone $q)->count();
        $logs = (clone $q)
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $rows = '';
        $details = [];
        if ($logs->count() > 0) {
            $i = $offset + 1;
            foreach ($logs as $log) {
                $parsed = $this->parseSmsUrl((string) ($log->url ?? ''));
                $result = $this->formatSmsResult((string) ($log->response ?? ''));
                $created = $log->created_at
                    ? Carbon::parse($log->created_at)->format('d-m-Y h:i:s a')
                    : '-';
                $channel = $log->modal === 'WHATSAPP_URL' ? 'WhatsApp' : 'SMS';
                $detailIndex = count($details);
                $details[] = $result['full'];

                $rows .= '<tr>
                    <td>' . $i . '</td>
                    <td class="sms-mobile">' . e($parsed['mobile']) . '</td>
                    <td>' . e($parsed['api']) . '</td>
                    <td class="sms-message" title="' . e($parsed['message']) . '">' . e(Str::limit($parsed['message'], 70)) . '</td>
                    <td>
                        <span class="sms-result-badge" title="' . e($result['short']) . '">' . e($result['short']) . '</span>
                        <button type="button" class="btn btn-link btn-sm p-0 ms-1 btn-view-sms-result"
                            data-detail-index="' . $detailIndex . '">View</button>
                    </td>
                    <td class="sms-date">' . e($created) . '</td>
                    <td><span class="badge bg-light text-dark">' . e($channel) . '</span></td>
                </tr>';
                $i++;
            }
        } else {
            $rows = '<tr><td colspan="7" class="text-center text-muted py-3">No SMS records found</td></tr>';
        }

        return response()->json([
            'type' => 'success',
            'rows' => $rows,
            'details' => $details,
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

    private function emptyResponse()
    {
        return response()->json([
            'type' => 'success',
            'rows' => '<tr><td colspan="7" class="text-center text-muted py-3">No SMS records found</td></tr>',
            'pagination' => [
                'page' => 1,
                'show' => 10,
                'total' => 0,
                'from' => 0,
                'to' => 0,
                'last_page' => 1,
            ],
        ]);
    }

    private function parseSmsUrl(string $url): array
    {
        $mobile = '-';
        $message = '-';
        $api = '-';

        if ($url === '') {
            return compact('mobile', 'message', 'api');
        }

        $parts = parse_url($url);
        $host = $parts['host'] ?? '';
        if ($host !== '') {
            $api = explode('.', str_replace('www.', '', $host))[0] ?: $host;
        }

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);

            foreach (['number', 'mobile', 'mob', 'MOB', 'msisdn', 'to', 'phone', 'receiver', 'sendto', 'contact', 'whatsapp', 'mobileno', 'mobile_no', 'phone_number', 'recipient'] as $key) {
                if (!empty($query[$key])) {
                    $mobile = preg_replace('/\D/', '', (string) $query[$key]);
                    $mobile = strlen($mobile) > 10 ? substr($mobile, -10) : $mobile;
                    break;
                }
            }
            if ($mobile === '-' && preg_match('/(?:91)?([6-9]\d{9})/', $url, $found)) {
                $mobile = $found[1];
            }

            foreach (['message', 'msg', 'text', 'MSG', 'sms', 'body'] as $key) {
                if (!empty($query[$key])) {
                    $message = urldecode((string) $query[$key]);
                    break;
                }
            }
        }

        return compact('mobile', 'message', 'api');
    }

    private function formatSmsResult(string $response): array
    {
        $response = trim($response);
        if ($response === '') {
            return ['short' => '-', 'full' => ''];
        }

        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $status = $this->firstResultText($decoded, ['status', 'Status', 'STATUS', 'ackStatus', 'success']);
            $reason = $this->firstResultText($decoded, ['message', 'Message', 'msg', 'error', 'Error', 'remark', 'description', 'response', 'Response']);
            if ($status !== '' && $reason !== '' && stripos($reason, $status) === false) {
                $short = $status.': '.$reason;
            } else {
                $short = $reason !== '' ? $reason : ($status !== '' ? $status : 'OK');
            }

            $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return [
                'short' => Str::limit($short, 80),
                'full' => $pretty ?: $response,
            ];
        }

        $plain = trim(strip_tags($response));

        return [
            'short' => Str::limit($plain !== '' ? $plain : $response, 80),
            'full' => $plain !== '' ? $plain : $response,
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function firstResultText(array $data, array $keys): string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data) || is_array($data[$key]) || is_object($data[$key])) {
                continue;
            }
            $value = trim((string) $data[$key]);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
