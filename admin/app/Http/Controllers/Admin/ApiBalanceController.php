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
        $result = admin_fetch_live_balance($api);
        if (! $result['ok']) {
            return response()->json(['type' => 'error', 'message' => $result['message']]);
        }

        if ((int) ($api->store_log ?? 0) === 1 && Schema::hasTable('apilogs')) {
            try {
                DB::table('apilogs')->insert([
                    'url' => $result['log_url'],
                    'modal' => 'BALANCE_CHECK',
                    'txnid' => $api->id,
                    'header' => '',
                    'request' => $result['log_request'],
                    'response' => mb_substr(admin_redact_secrets($result['raw']), 0, 65000),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            } catch (\Throwable $e) {
            }
        }

        return response()->json([
            'type' => 'success',
            'balance' => $result['balance'],
            'raw' => $result['raw'],
        ]);
    }
}
