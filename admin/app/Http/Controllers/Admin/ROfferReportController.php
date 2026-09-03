<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ROfferReportController extends Controller
{
    private array $modals = ['Roffer', 'ROFFER', 'ROF'];

    public function index()
    {
        $defaultDate = Carbon::today()->format('Y-m-d');
        if (Schema::hasTable('apilogs')) {
            $last = DB::table('apilogs')
                ->whereIn('modal', $this->modals)
                ->orderByDesc('id')
                ->value('created_at');
            if ($last) {
                $defaultDate = Carbon::parse($last)->format('Y-m-d');
            }
        }

        return view('admin.recharge-reports.r-offer-report', compact('defaultDate'));
    }

    private function base(Request $request)
    {
        $q = DB::table('apilogs')->where(function ($w) {
            $w->whereIn('modal', $this->modals)
                ->orWhere('modal', 'like', '%Roffer%')
                ->orWhere('url', 'like', '%offer=roffer%')
                ->orWhere('url', 'like', '%roffer%');
        });

        $from = $request->from_date ?: Carbon::today()->format('Y-m-d');
        $to = $request->to_date ?: $from;
        $q->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);

        if ($request->number) {
            $term = trim((string) $request->number);
            $q->where(function ($w) use ($term) {
                $w->where('txnid', 'like', "%{$term}%")
                    ->orWhere('url', 'like', "%{$term}%")
                    ->orWhere('request', 'like', "%{$term}%")
                    ->orWhere('response', 'like', "%{$term}%");
            });
        }

        return $q;
    }

    public function list(Request $request)
    {
        if (! Schema::hasTable('apilogs')) {
            return response()->json([
                'type' => 'success',
                'rows' => '<tr><td colspan="8" class="text-center text-muted py-4">No data available in table</td></tr>',
                'summary' => $this->emptySummary(),
                'pagination' => ['page' => 1, 'show' => 10, 'total' => 0, 'from' => 0, 'to' => 0, 'last_page' => 1],
            ]);
        }

        $limit = in_array((int) $request->show, [10, 25, 50, 100], true) ? (int) $request->show : 10;
        $page = max(1, (int) ($request->page ?: 1));
        $offset = ($page - 1) * $limit;

        $base = $this->base($request);
        $total = (clone $base)->count();
        $logs = (clone $base)->orderByDesc('id')->offset($offset)->limit($limit)->get();

        $successCnt = 0;
        $failureCnt = 0;
        $html = '';
        if ($logs->count()) {
            foreach ($logs as $log) {
                $parsed = $this->parseLog($log);
                if ($parsed['ok']) {
                    $successCnt++;
                } else {
                    $failureCnt++;
                }
                $badge = $parsed['ok'] ? 'success' : 'danger';
                $html .= '<tr>
                    <td><strong>'.e($log->txnid ?: ('#'.$log->id)).'</strong></td>
                    <td>'.e($log->created_at).'</td>
                    <td>'.e($parsed['number']).'</td>
                    <td>'.e($parsed['operator']).'</td>
                    <td>'.e($log->modal ?: 'Roffer').'</td>
                    <td><span class="badge bg-'.$badge.'">'.e($parsed['status']).'</span></td>
                    <td>'.e($parsed['offers']).'</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-view-roffer"
                            data-req="'.e($parsed['request']).'" data-res="'.e($parsed['response']).'">View</button>
                    </td>
                </tr>';
            }
        } else {
            $html = '<tr><td colspan="8" class="text-center text-muted py-4">No R-Offer checks found for this date</td></tr>';
        }

        return response()->json([
            'type' => 'success',
            'rows' => $html,
            'summary' => [
                'success_amt' => number_format($successCnt, 0),
                'success_cnt' => $successCnt,
                'pending_amt' => '0',
                'pending_cnt' => 0,
                'failure_amt' => number_format($failureCnt, 0),
                'failure_cnt' => $failureCnt,
                'refunded_amt' => '0',
                'refunded_cnt' => 0,
            ],
            'pagination' => [
                'page' => $page,
                'show' => $limit,
                'total' => $total,
                'from' => $total ? $offset + 1 : 0,
                'to' => min($offset + $limit, $total),
                'last_page' => max(1, (int) ceil($total / max($limit, 1))),
            ],
        ]);
    }

    public function download(Request $request)
    {
        if (! Schema::hasTable('apilogs')) {
            return response('No data', 200, ['Content-Type' => 'text/plain']);
        }

        $logs = $this->base($request)->orderByDesc('id')->limit(5000)->get();
        $filename = 'r-offer-report-'.date('Ymd-His').'.csv';

        return response()->stream(function () use ($logs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['TXN ID', 'DATE', 'NUMBER', 'OPERATOR', 'TYPE', 'STATUS', 'OFFERS']);
            foreach ($logs as $log) {
                $parsed = $this->parseLog($log);
                fputcsv($out, [
                    $log->txnid,
                    $log->created_at,
                    $parsed['number'],
                    $parsed['operator'],
                    $log->modal,
                    $parsed['status'],
                    $parsed['offers'],
                ]);
            }
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function emptySummary(): array
    {
        return [
            'success_amt' => '0',
            'success_cnt' => 0,
            'pending_amt' => '0',
            'pending_cnt' => 0,
            'failure_amt' => '0',
            'failure_cnt' => 0,
            'refunded_amt' => '0',
            'refunded_cnt' => 0,
        ];
    }

    private function parseLog(object $log): array
    {
        $hay = (string) ($log->url ?? '').' '.(string) ($log->request ?? '');
        $number = '-';
        $operator = '-';
        if (preg_match('/[?&]tel=([^&]+)/i', $hay, $m)) {
            $number = urldecode($m[1]);
        } elseif (preg_match('/\b(\d{10})\b/', $hay, $m)) {
            $number = $m[1];
        }
        if (preg_match('/[?&]operator=([^&]+)/i', $hay, $m)) {
            $operator = urldecode($m[1]);
        }

        $response = (string) ($log->response ?? '');
        $offers = $this->offerCount($response);
        $ok = $offers > 0;

        $request = (string) ($log->url ?: $log->request);
        $request = preg_replace('/([?&]apikey=)[^&]+/i', '$1***', $request) ?: $request;

        return [
            'number' => $number,
            'operator' => $operator === '0' ? '-' : $operator,
            'ok' => $ok,
            'status' => $ok ? 'Offers Found' : 'No Offers',
            'offers' => $offers,
            'request' => Str::limit($request, 2000),
            'response' => Str::limit($response, 8000),
        ];
    }

    private function offerCount(string $response): int
    {
        if ($response === '') {
            return 0;
        }
        $data = json_decode($response, true);
        if (! is_array($data)) {
            return 0;
        }
        foreach (['records', 'data', 'Roffer', 'Plans', 'offers', 'INFO'] as $key) {
            if (! isset($data[$key]) || ! is_array($data[$key]) || $data[$key] === []) {
                continue;
            }
            if (isset($data[$key][0]) || array_is_list($data[$key])) {
                return count($data[$key]);
            }
        }

        return 0;
    }
}
