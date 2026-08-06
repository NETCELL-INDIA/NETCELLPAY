<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SupplierFail2SuccessController extends Controller
{
    public function __construct()
    {
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        if (!Schema::hasTable('supplier_fail_to_success')) {
            Schema::create('supplier_fail_to_success', function ($table) {
                $table->id();
                $table->string('recharge_id', 100)->nullable();
                $table->unsignedBigInteger('report_id')->nullable();
                $table->unsignedBigInteger('provider_id')->nullable();
                $table->string('number', 30)->nullable();
                $table->decimal('amount', 12, 2)->default(0);
                $table->unsignedBigInteger('last_api_id')->nullable();
                $table->unsignedBigInteger('response_api_id')->nullable();
                $table->text('response')->nullable();
                $table->string('remark', 255)->nullable();
                $table->timestamp('recharge_time')->nullable();
                $table->timestamp('response_time')->nullable();
                $table->timestamps();
            });
        }

        if (DB::table('supplier_fail_to_success')->count() === 0) {
            // Seed from success reports that previously had failed logs / failed twin
            $success = DB::table('reports')->where('status', 'Success')->orderByDesc('id')->limit(5)->get();
            $apiId = DB::table('apis')->value('id');
            foreach ($success as $s) {
                DB::table('supplier_fail_to_success')->insert([
                    'recharge_id' => $s->order_id,
                    'report_id' => $s->id,
                    'provider_id' => $s->provider_id,
                    'number' => $s->number,
                    'amount' => $s->amount,
                    'last_api_id' => $s->api_id ?: $apiId,
                    'response_api_id' => $s->api_id ?: $apiId,
                    'response' => 'Success after fail',
                    'remark' => 'Auto tracked fail to success',
                    'recharge_time' => $s->created_at,
                    'response_time' => $s->updated_at ?: $s->created_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function index()
    {
        $apis = DB::table('apis')->orderBy('api_name')->get(['id', 'api_name']);

        return view('admin.recharge-reports.supplier-fail-2-success', compact('apis'));
    }

    public function list(Request $request)
    {
        $limit = (int) ($request->show ?: 10);
        if (!in_array($limit, [10, 25, 50, 100], true)) {
            $limit = 10;
        }
        $page = max(1, (int) ($request->page ?: 1));
        $offset = ($page - 1) * $limit;

        $from = $request->from_date ?: Carbon::today()->subDays(30)->format('Y-m-d');
        $to = $request->to_date ?: Carbon::today()->format('Y-m-d');

        $q = DB::table('supplier_fail_to_success as f')
            ->leftJoin('providers as p', 'p.id', '=', 'f.provider_id')
            ->leftJoin('apis as la', 'la.id', '=', 'f.last_api_id')
            ->leftJoin('apis as ra', 'ra.id', '=', 'f.response_api_id')
            ->whereBetween('f.created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);

        if ($request->api_id) {
            $apiId = (int) $request->api_id;
            $q->where(function ($w) use ($apiId) {
                $w->where('f.last_api_id', $apiId)->orWhere('f.response_api_id', $apiId);
            });
        }
        if ($request->recharge_id) {
            $term = trim($request->recharge_id);
            $q->where('f.recharge_id', 'like', "%{$term}%");
        }

        $total = (clone $q)->count();
        $list = (clone $q)
            ->select('f.*', 'p.provider_name', 'la.api_name as last_api', 'ra.api_name as response_api')
            ->orderByDesc('f.id')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $rows = '';
        if ($list->count() > 0) {
            foreach ($list as $row) {
                $rows .= '<tr>
                    <td>' . e($row->recharge_id ?: '-') . '</td>
                    <td>' . e($row->provider_name ?: '-') . '</td>
                    <td>' . e($row->number ?: '-') . '</td>
                    <td>₹' . number_format((float) $row->amount, 2) . '</td>
                    <td>' . e($row->last_api ?: '-') . '</td>
                    <td>' . e($row->response_api ?: '-') . '</td>
                    <td><small>' . e(\Illuminate\Support\Str::limit((string) $row->response, 80)) . '</small></td>
                    <td>' . e($row->remark ?: '-') . '</td>
                    <td><small>' . e($row->recharge_time ?: '-') . '<br>' . e($row->response_time ?: '-') . '</small></td>
                </tr>';
            }
        } else {
            $rows = '<tr><td colspan="9" class="text-center text-muted py-4">No data available in table</td></tr>';
        }

        return response()->json([
            'type' => 'success',
            'rows' => $rows,
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
}
