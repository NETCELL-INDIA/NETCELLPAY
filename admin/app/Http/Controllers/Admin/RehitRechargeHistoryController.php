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
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        if (Schema::hasTable('rehit_recharge_logs')) {
            return;
        }

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

    public static function logAttempt(object $report, int $apiId, string $mode = 'RETRY'): void
    {
        try {
            if (! Schema::hasTable('rehit_recharge_logs')) {
                (new self())->ensureTable();
            }
            DB::table('rehit_recharge_logs')->insert([
                'report_id' => $report->id ?? null,
                'recharge_id' => $report->order_id ?? null,
                'user_id' => $report->user_id ?? null,
                'provider_id' => $report->provider_id ?? null,
                'api_id' => $apiId ?: ($report->api_id ?? null),
                'number' => $report->number ?? null,
                'amount' => $report->amount ?? 0,
                'operator_id' => $report->operator_id ?? null,
                'mode' => strtoupper($mode),
                'ip_address' => request()->ip() ?: '-',
                'rehit_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
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
        $date = trim((string) ($request->from_date ?? ''));

        $q = DB::table('rehit_recharge_logs as h')
            ->leftJoin('users as u', 'u.id', '=', 'h.user_id')
            ->leftJoin('providers as p', 'p.id', '=', 'h.provider_id')
            ->leftJoin('apis as a', 'a.id', '=', 'h.api_id')
            ->leftJoin('reports as rp', 'rp.id', '=', 'h.report_id');

        if ($date !== '') {
            $q->whereDate('h.rehit_at', $date);
        }

        if ($request->recharge_id) {
            $q->where('h.recharge_id', 'like', '%'.trim($request->recharge_id).'%');
        }

        $total = (clone $q)->count('h.id');
        $select = [
            'h.*',
            'u.outlet_name',
            'u.first_name',
            'u.mobile_number',
            'p.provider_name',
            'a.api_name',
            'rp.total_amount as mrp',
            'rp.status as recharge_status',
        ];
        if (Schema::hasColumn('reports', 'api_operator_id')) {
            $select[] = 'rp.api_operator_id';
        }
        $rowsData = (clone $q)->select($select)
            ->orderByDesc('h.id')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $html = '';
        if ($rowsData->count()) {
            foreach ($rowsData as $r) {
                $user = trim(($r->outlet_name ?: $r->first_name ?: 'User').' / '.($r->mobile_number ?: '-'));
                $mrp = $r->mrp !== null ? (float) $r->mrp : (float) $r->amount;
                $status = strtoupper((string) ($r->recharge_status ?: '-'));
                $statusClass = in_array($status, ['SUCCESS'], true) ? 'bg-success' : (in_array($status, ['FAILED', 'FAILURE'], true) ? 'bg-danger' : 'bg-warning text-dark');
                $mode = strtoupper((string) ($r->mode ?: 'RETRY'));
                $html .= '<tr>
                    <td>'.e($r->recharge_id ?: '-').'</td>
                    <td>'.e($r->rehit_at).'</td>
                    <td>'.e($user).'</td>
                    <td>'.e($r->provider_name ?: '-').'</td>
                    <td>'.e($r->number ?: '-').'</td>
                    <td><strong>₹'.number_format($mrp, 2).'</strong></td>
                    <td>₹'.number_format((float) $r->amount, 2).'</td>
                    <td><span class="badge '.$statusClass.'">'.e($status).'</span></td>
                    <td>'.e($r->api_name ?: '-').'</td>
                    <td><small>'.e(($r->operator_id ?: '-').' / '.($r->api_operator_id ?: '-')).'</small></td>
                    <td>'.e($mode.' / '.($r->ip_address ?: '-')).'</td>
                </tr>';
            }
        } else {
            $html = '<tr><td colspan="11" class="text-center text-muted py-4">No resend yet. Use Pending Report → Resend or Rehit.</td></tr>';
        }

        return response()->json([
            'type' => 'success',
            'rows' => $html,
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
}
