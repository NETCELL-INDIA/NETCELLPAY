<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FundCreditDebitController extends Controller
{
    public function index()
    {
        return view('admin.fund.credit-debit');
    }

    public function search(Request $request)
    {
        $term = trim((string) $request->q);
        if ($term === '') {
            return response()->json(['type' => 'success', 'data' => []]);
        }

        $users = DB::table('users')
            ->where(function ($w) {
                $w->whereNull('deleted_at')->orWhere('deleted_at', '!=', 1);
            })
            ->where('role_id', '!=', 1)
            ->where(function ($w) use ($term) {
                $w->where('mobile_number', 'like', "%{$term}%")
                    ->orWhere('outlet_name', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('id', $term);
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'outlet_name', 'first_name', 'last_name', 'mobile_number', 'wallet_balance', 'status']);

        $data = [];
        foreach ($users as $u) {
            $name = trim(($u->first_name ?? '').' '.($u->last_name ?? ''));
            if ($name === '') {
                $name = $u->outlet_name ?: 'User';
            }
            $data[] = [
                'id' => (int) $u->id,
                'name' => $name,
                'outlet' => (string) ($u->outlet_name ?? ''),
                'mobile' => (string) ($u->mobile_number ?? ''),
                'wallet' => number_format((float) $u->wallet_balance, 2, '.', ''),
                'wallet_text' => '₹ '.number_format((float) $u->wallet_balance, 2),
                'active' => (string) $u->status === '1',
            ];
        }

        return response()->json(['type' => 'success', 'data' => $data]);
    }
}
