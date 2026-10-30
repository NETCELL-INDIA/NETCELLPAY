<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PendingRechargeStatusService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WrongRechargeAdminController extends Controller
{
    public function index(Request $request)
    {
        $done = $request->query('status') === 'done';
        $rows = collect();
        if (Schema::hasTable('wrong_recharge_requests')) {
            $q = DB::table('wrong_recharge_requests as w')
                ->leftJoin('users as u', 'u.id', '=', 'w.user_id')
                ->select('w.*', 'u.outlet_name', 'u.mobile_number', 'u.first_name')
                ->orderByDesc('w.id')
                ->limit(200);
            if ($done) {
                $q->whereIn('w.status', ['Rejected', 'Refunded']);
            } else {
                $q->whereIn('w.status', ['Pending', 'Approved']);
            }
            $rows = $q->get();
        }

        return view('admin.recharge-reports.wrong-recharge', [
            'rows' => $rows,
            'done' => $done,
        ]);
    }

    public function update(Request $request)
    {
        $action = (string) $request->input('action');
        if (! in_array($action, ['Approved', 'Rejected', 'Refunded'], true)) {
            return response()->json(['type' => 'error', 'message' => 'Invalid action']);
        }
        if (! Schema::hasTable('wrong_recharge_requests')) {
            return response()->json(['type' => 'error', 'message' => 'No requests yet']);
        }

        $id = (int) $request->input('id');
        $note = mb_substr(trim((string) $request->input('admin_note', '')), 0, 500);

        try {
            DB::beginTransaction();
            $row = DB::table('wrong_recharge_requests')->where('id', $id)->lockForUpdate()->first();
            if (! $row || ! in_array($row->status, ['Pending', 'Approved'], true)) {
                DB::rollBack();

                return response()->json(['type' => 'error', 'message' => 'Request is already closed']);
            }

            if ($action === 'Refunded') {
                $report = DB::table('reports')->where('id', $row->report_id)->lockForUpdate()->first();
                if (! $report || (string) $report->status !== 'Success' || (int) $report->user_id !== (int) $row->user_id) {
                    DB::rollBack();

                    return response()->json(['type' => 'error', 'message' => 'Original recharge is not a successful row for this user. Wallet was not changed.']);
                }
                $this->reverseCommission($report);
                $credited = PendingRechargeStatusService::refundReport((int) $report->id, 'Wrong number refund');
                if (! $credited) {
                    DB::rollBack();

                    return response()->json(['type' => 'error', 'message' => 'A refund already exists for this order. Wallet was not changed.']);
                }
                DB::table('reports')->where('id', $report->id)->update([
                    'status' => 'Refunded',
                    'remark' => 'Wrong number refund. Correct number '.$row->correct_number,
                    'updated_at' => Carbon::now(),
                ]);
            }

            DB::table('wrong_recharge_requests')->where('id', $id)->update([
                'status' => $action,
                'admin_note' => $note,
                'updated_at' => Carbon::now(),
            ]);
            DB::commit();
        } catch (\Throwable $e) {
            try {
                DB::rollBack();
            } catch (\Throwable $x) {
            }

            return response()->json(['type' => 'error', 'message' => 'Could not update the request. No wallet change was saved.']);
        }

        if ($action === 'Refunded') {
            try {
                \Helper::sendApiPartnerRechargeCallback((int) DB::table('wrong_recharge_requests')->where('id', $id)->value('report_id'));
            } catch (\Throwable $e) {
            }
        }

        $message = match ($action) {
            'Approved' => 'Approved for follow-up. Wallet was not changed and no new recharge was sent.',
            'Rejected' => 'Request rejected. Wallet was not changed.',
            default => 'Wallet refunded for the original recharge. A new recharge to the correct number was not sent.',
        };

        return response()->json(['type' => 'success', 'message' => $message]);
    }

    private function reverseCommission(object $recharge): void
    {
        $commissions = DB::table('reports')
            ->where('order_id', $recharge->order_id)
            ->where('transaction_type', 'Commission')
            ->where('status', 'Success')
            ->get();
        foreach ($commissions as $commission) {
            $already = DB::table('reports')
                ->where('order_id', $recharge->order_id)
                ->where('user_id', $commission->user_id)
                ->where('transaction_type', 'Reverse Commission')
                ->where('status', 'Success')
                ->exists();
            if ($already) {
                continue;
            }
            $user = DB::table('users')->where('id', $commission->user_id)->lockForUpdate()->first();
            if (! $user) {
                continue;
            }
            $opening = (float) $user->wallet_balance;
            $debit = (float) $commission->commission;
            DB::table('users')->where('id', $user->id)->decrement('wallet_balance', $debit);
            $closing = (float) DB::table('users')->where('id', $user->id)->value('wallet_balance');
            $row = [
                'user_id' => $commission->user_id,
                'parent__Id' => $commission->id,
                'order_id' => $commission->order_id,
                'number' => $commission->number,
                'amount' => $commission->amount,
                'total_amount' => $commission->total_amount,
                'commission' => $commission->commission,
                'fund_type' => 'Debit',
                'transaction_type' => 'Reverse Commission',
                'provider_id' => $commission->provider_id,
                'service_id' => $commission->service_id,
                'api_id' => $commission->api_id,
                'remark' => 'Reverse commission for wrong-number refund '.$recharge->order_id,
                'status' => 'Success',
                'path' => $commission->path,
                'ip_address' => $commission->ip_address,
                'opening_balance' => $opening,
                'closing_balance' => $closing,
                'transaction_date' => Carbon::now()->format('Y-m-d H:i:s').':'.rand(111, 999),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            $columns = array_flip(Schema::getColumnListing('reports'));
            DB::table('reports')->insert(array_intersect_key($row, $columns));
        }
    }
}
