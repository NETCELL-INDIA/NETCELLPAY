<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WrongRechargeController extends Controller
{
    public function store(Request $request)
    {
        $userId = (int) session('user_id');
        if ($userId <= 0) {
            $userId = (int) $request->input('user_id');
        }
        if ($userId <= 0) {
            return response()->json(['type' => 'error', 'message' => 'Login required']);
        }

        $correct = preg_replace('/\D+/', '', (string) $request->input('correct_number'));
        $request->merge(['correct_number' => $correct]);

        $validator = \Validator::make($request->all(), [
            'report_id' => 'required|integer|min:1',
            'correct_number' => ['required', 'regex:/^[0-9]{5,16}$/'],
            'reason' => 'nullable|string|max:500',
        ]);
        if ($validator->fails()) {
            return response()->json(['type' => 'error', 'message' => $validator->errors()->first()]);
        }

        $this->ensureTable();

        $report = DB::table('reports')
            ->where('id', (int) $request->report_id)
            ->where('user_id', $userId)
            ->where('transaction_type', 'Recharge')
            ->first();
        if (! $report) {
            return response()->json(['type' => 'error', 'message' => 'Recharge not found']);
        }
        if ((string) $report->status !== 'Success') {
            return response()->json(['type' => 'error', 'message' => 'Only a successful recharge can be marked as a wrong number']);
        }

        $wrong = preg_replace('/\D+/', '', (string) $report->number);
        if ($correct === $wrong) {
            return response()->json(['type' => 'error', 'message' => 'Correct number must be different from the recharged number']);
        }

        $open = DB::table('wrong_recharge_requests')
            ->where('report_id', $report->id)
            ->whereIn('status', ['Pending', 'Approved', 'Refunded'])
            ->first();
        if ($open) {
            return response()->json([
                'type' => 'success',
                'message' => 'Request '.$open->public_id.' is already '.$open->status.'. Wallet is unchanged until an administrator refunds it.',
                'reference' => $open->public_id,
            ]);
        }

        $publicId = 'WR-'.date('Ymd').'-'.strtoupper(Str::random(6));
        DB::table('wrong_recharge_requests')->insert([
            'public_id' => $publicId,
            'user_id' => $userId,
            'report_id' => $report->id,
            'order_id' => (string) ($report->order_id ?? ''),
            'wrong_number' => (string) $report->number,
            'correct_number' => $correct,
            'amount' => $report->total_amount ?? $report->amount,
            'reason' => trim((string) $request->input('reason', '')),
            'status' => 'Pending',
            'admin_note' => '',
            'ip_address' => (string) $request->ip(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return response()->json([
            'type' => 'success',
            'message' => 'Wrong-number request '.$publicId.' is pending review. This does not refund the wallet or send a new recharge.',
            'reference' => $publicId,
        ]);
    }

    private function ensureTable(): void
    {
        if (Schema::hasTable('wrong_recharge_requests')) {
            return;
        }
        Schema::create('wrong_recharge_requests', function ($table) {
            $table->id();
            $table->string('public_id', 32)->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('report_id')->index();
            $table->string('order_id', 64)->nullable()->index();
            $table->string('wrong_number', 20);
            $table->string('correct_number', 20);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('reason', 500)->nullable();
            $table->string('status', 24)->index();
            $table->string('admin_note', 500)->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->timestamps();
        });
    }
}
