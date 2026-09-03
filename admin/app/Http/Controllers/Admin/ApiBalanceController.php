<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApiBalanceController extends Controller
{
    public function index()
    {
        return view('admin.apis.balance-check');
    }

    public function list()
    {
        $q = DB::table('apis')->where(function ($w) {
            $w->whereNull('deleted_at')->orWhere('deleted_at', '!=', 1);
        });
        $rows = $q->orderBy('api_name')->get();
        $html = '';
        foreach ($rows as $api) {
            $has = trim((string) ($api->balance_check_url ?? ''));
            $ready = $has !== '' && $has !== '0';
            $html .= '<tr data-id="'.$api->id.'">
                <td>'.e($api->id).'</td>
                <td>'.e($api->api_name).'</td>
                <td>'.e($api->api_type ?: 'recharge').'</td>
                <td>'.((string) $api->status === '1' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Off</span>').'</td>
                <td>'.($ready ? '<span class="badge bg-info">URL set</span>' : '<span class="badge bg-warning text-dark">No URL</span>').'</td>
                <td class="bal-cell fw-semibold">—</td>
                <td class="bal-raw small text-muted" style="max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">—</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-primary btn-check-bal" '.($ready ? '' : 'disabled').' data-id="'.$api->id.'">Check</button>
                </td>
            </tr>';
        }
        if ($html === '') {
            $html = '<tr><td colspan="8" class="text-center text-muted">No APIs</td></tr>';
        }

        return response()->json(['type' => 'success', 'rows' => $html]);
    }

    public function check(Request $request)
    {
        $api = DB::table('apis')->where('id', (int) $request->id)->first();
        if (! $api) {
            return response()->json(['type' => 'error', 'message' => 'API not found']);
        }
        $url = trim((string) ($api->balance_check_url ?? ''));
        if ($url === '' || $url === '0') {
            return response()->json(['type' => 'error', 'message' => 'Balance check URL not set']);
        }
        $url = str_replace(
            ['{API_USERNAME}', '{API_PASSWORD}', '{API_KEY}'],
            [(string) ($api->api_username ?? ''), (string) ($api->api_password ?? ''), (string) ($api->api_key ?? '')],
            $url
        );

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => $api->api_method ?: 'GET',
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        $raw = ($response !== false && $response !== '') ? (string) $response : ($err ?: 'Empty response');
        $parsed = $this->parseBalance($raw);

        if ((int) ($api->store_log ?? 0) === 1 && Schema::hasTable('apilogs')) {
            try {
                DB::table('apilogs')->insert([
                    'url' => $url,
                    'modal' => 'BALANCE_CHECK',
                    'txnid' => $api->id,
                    'header' => '',
                    'request' => '',
                    'response' => mb_substr($raw, 0, 65000),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            } catch (\Throwable $e) {
            }
        }

        return response()->json([
            'type' => 'success',
            'balance' => $parsed,
            'raw' => mb_substr($raw, 0, 2000),
        ]);
    }

    private function parseBalance(string $raw): string
    {
        $data = json_decode($raw, true);
        if (is_array($data)) {
            $found = $this->findNumeric($data, ['balance', 'Balance', 'wallet', 'Wallet', 'available_balance', 'AvailableBalance', 'bal', 'amount']);
            if ($found !== null) {
                return '₹ '.number_format((float) $found, 2);
            }
        }
        if (preg_match('/"(?:balance|wallet|available_balance)"\s*:\s*"?([0-9]+(?:\.[0-9]+)?)/i', $raw, $m)) {
            return '₹ '.number_format((float) $m[1], 2);
        }

        return 'See response';
    }

    private function findNumeric(array $data, array $keys): ?float
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                return (float) $data[$key];
            }
        }
        foreach ($data as $val) {
            if (is_array($val)) {
                $n = $this->findNumeric($val, $keys);
                if ($n !== null) {
                    return $n;
                }
            }
        }

        return null;
    }
}
