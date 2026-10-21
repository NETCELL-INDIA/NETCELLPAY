<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BusinessToolsController extends Controller
{
    public function signups()
    {
        $rows = DB::table('users')
            ->whereNotIn('role_id', [1, 2])
            ->whereIn('kyc_status', ['Pending', 'Under Process'])
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'first_name', 'last_name', 'mobile_number', 'outlet_name', 'kyc_status', 'created_at']);

        $docs = [];
        if (Schema::hasTable('user_kyc_documents')) {
            $docs = DB::table('user_kyc_documents')->orderByDesc('id')->limit(300)->get()->groupBy('user_id');
        }

        return view('admin.business.signups', compact('rows', 'docs'));
    }

    public function signupStatus(Request $request)
    {
        $status = (string) $request->input('kyc_status');
        if (!in_array($status, ['Approved', 'Rejected', 'Pending'], true)) {
            return response()->json(['type' => 'error', 'message' => 'Invalid status.']);
        }

        $updated = DB::table('users')
            ->where('id', (int) $request->input('user_id'))
            ->whereNotIn('role_id', [1, 2])
            ->update(['kyc_status' => $status]);

        return response()->json([
            'type' => $updated ? 'success' : 'error',
            'message' => $updated ? 'KYC status updated.' : 'User not updated.',
        ]);
    }

    public function referrals()
    {
        $rows = collect();
        if (Schema::hasTable('reports')) {
            $rows = DB::table('reports')
                ->where('transaction_type', 'Referral Bonus')
                ->orderByDesc('id')
                ->limit(200)
                ->get(['id', 'user_id', 'amount', 'order_id', 'status', 'created_at']);
        }

        return view('admin.business.referrals', compact('rows'));
    }

    public function khata()
    {
        $this->ensureKhata();
        $rows = DB::table('khatabook_entries')->orderByDesc('id')->limit(200)->get();
        $outstanding = DB::table('khatabook_entries')
            ->select('user_id', DB::raw("SUM(CASE WHEN entry_type='credit' THEN amount ELSE -amount END) as due"))
            ->groupBy('user_id')
            ->get();

        return view('admin.business.khata', compact('rows', 'outstanding'));
    }

    public function khataSave(Request $request)
    {
        $this->ensureKhata();
        $type = $request->input('entry_type');
        $amount = (float) $request->input('amount');
        $userId = (int) $request->input('user_id');
        if (!in_array($type, ['credit', 'debit'], true) || $amount <= 0 || $userId <= 0) {
            return response()->json(['type' => 'error', 'message' => 'User, type and amount are required.']);
        }
        if (!DB::table('users')->where('id', $userId)->exists()) {
            return response()->json(['type' => 'error', 'message' => 'User not found.']);
        }

        DB::table('khatabook_entries')->insert([
            'user_id' => $userId,
            'entry_type' => $type,
            'amount' => $amount,
            'note' => mb_substr((string) $request->input('note', ''), 0, 255),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['type' => 'success', 'message' => 'Ledger entry saved. Wallet was not changed.']);
    }

    public function tds(Request $request)
    {
        $percent = max(0, min(30, (float) $request->input('percent', 5)));
        $from = ($request->input('from_date') ?: now()->startOfMonth()->format('Y-m-d')).' 00:00:00';
        $to = ($request->input('to_date') ?: now()->format('Y-m-d')).' 23:59:59';

        $rows = DB::table('reports')
            ->select('user_id', DB::raw('SUM(commission) as commission'))
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'Success')
            ->groupBy('user_id')
            ->orderByDesc('commission')
            ->limit(500)
            ->get();

        if ($request->input('download') === '1') {
            $csv = "user_id,commission,tds_percent,tds_amount\n";
            foreach ($rows as $row) {
                $commission = (float) $row->commission;
                $tds = round($commission * $percent / 100, 2);
                $csv .= (int) $row->user_id.','.$commission.','.$percent.','.$tds."\n";
            }

            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="tds-export.csv"',
            ]);
        }

        return view('admin.business.tds', compact('rows', 'percent', 'from', 'to'));
    }

    public function serviceReport(string $kind)
    {
        $types = $kind === 'aeps' ? ['AEPS', 'Aeps'] : ['Money Transfer', 'DMT'];
        $title = $kind === 'aeps' ? 'AEPS Report' : 'DMT Report';
        $rows = DB::table('reports')
            ->whereIn('transaction_type', $types)
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'user_id', 'number', 'amount', 'status', 'order_id', 'transaction_type', 'created_at']);

        return view('admin.business.service-report', compact('rows', 'title', 'kind'));
    }

    private function ensureKhata(): void
    {
        if (Schema::hasTable('khatabook_entries')) {
            return;
        }
        Schema::create('khatabook_entries', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('entry_type', 16);
            $table->decimal('amount', 12, 2);
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });
    }
}
