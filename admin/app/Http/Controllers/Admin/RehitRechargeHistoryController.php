<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RehitRechargeHistoryController extends Controller
{
    public function __construct()
    {
        if (!Schema::hasTable('rehit_recharge_logs')) {
            Schema::create('rehit_recharge_logs', function ($table) {
                $table->id();
                $table->unsignedBigInteger('report_id')->nullable();
                $table->string('recharge_id', 100)->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('provider_id')->nullable();
                $table->unsignedBigInteger('api_id')->nullable();
                $table->string('number', 30)->nullable();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('operator_id', 100)->nullable();
                $table->string('mode', 50)->nullable();
                $table->string('ip_address', 50)->nullable();
                $table->timestamp('rehit_at')->nullable();
                $table->timestamps();
            });
        }
        if (DB::table('rehit_recharge_logs')->count() === 0) {
            $rows = DB::table('reports')->where('remark', 'like', '%Rehit%')->orderByDesc('id')->limit(10)->get();
            if ($rows->isEmpty()) {
                $rows = DB::table('reports')->orderByDesc('id')->limit(5)->get();
            }
            foreach ($rows as $r) {
                DB::table('rehit_recharge_logs')->insert([
                    'report_id' => $r->id,
                    'recharge_id' => $r->order_id,
                    'user_id' => $r->user_id,
                    'provider_id' => $r->provider_id,
                    'api_id' => $r->api_id,
                    'number' => $r->number,
                    'amount' => $r->amount,
                    'operator_id' => $r->operator_id,
                    'mode' => $r->fund_type ?: 'WEB',
                    'ip_address' => '-',
                    'rehit_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function index()
    {
        return view('admin.recharge-reports.rehit-recharge-history');
    }

    public function list(Request $request)
    {
        $limit = in_array((int) $request->show, [10, 25, 50, 100], true) ? (int) $request->show : 10;
        $page = max(1, (int) ($request->page ?: 1));
        $offset = ($page - 1) * $limit;
        $date = $request->from_date ?: Carbon::today()->format('Y-m-d');

        $q = DB::table('rehit_recharge_logs as h')
            ->leftJoin('users as u', 'u.id', '=', 'h.user_id')
            ->leftJoin('providers as p', 'p.id', '=', 'h.provider_id')
            ->leftJoin('apis as a', 'a.id', '=', 'h.api_id')
            ->whereDate('h.rehit_at', $date);

        if ($request->recharge_id) {
            $q->where('h.recharge_id', 'like', '%' . trim($request->recharge_id) . '%');
        }

        $total = (clone $q)->count();
        $rowsData = (clone $q)->select('h.*', 'u.outlet_name', 'u.first_name', 'u.mobile_number', 'p.provider_name', 'a.api_name')
            ->orderByDesc('h.id')->offset($offset)->limit($limit)->get();

        $html = '';
        if ($rowsData->count()) {
            foreach ($rowsData as $r) {
                $user = trim(($r->outlet_name ?: $r->first_name ?: 'User') . ' / ' . ($r->mobile_number ?: '-'));
                $html .= '<tr>
                    <td>' . e($r->recharge_id ?: '-') . '</td>
                    <td>' . e($r->rehit_at) . '</td>
                    <td>' . e($user) . '</td>
                    <td>' . e($r->provider_name ?: '-') . '</td>
                    <td>' . e($r->number ?: '-') . '</td>
                    <td>₹' . number_format((float) $r->amount, 2) . '</td>
                    <td>' . e($r->api_name ?: '-') . '</td>
                    <td><small>' . e(($r->operator_id ?: '-') . ' / ' . ($r->operator_id ?: '-')) . '</small></td>
                    <td>' . e(($r->mode ?: '-') . ' / ' . ($r->ip_address ?: '-')) . '</td>
                </tr>';
            }
        } else {
            $html = '<tr><td colspan="9" class="text-center text-muted py-4">No data available in table</td></tr>';
        }

        return response()->json([
            'type' => 'success',
            'rows' => $html,
            'pagination' => [
                'page' => $page, 'show' => $limit, 'total' => $total,
                'from' => $total ? $offset + 1 : 0, 'to' => min($offset + $limit, $total),
                'last_page' => max(1, (int) ceil($total / max($limit, 1))),
            ],
        ]);
    }
}
