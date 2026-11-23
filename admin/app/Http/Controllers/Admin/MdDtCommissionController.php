<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MdDtCommissionController extends Controller
{
    public function index()
    {
        return view('admin.admin-reports.md-dt-commission');
    }

    public function list(Request $post)
    {
        $from = $post->from_date ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $to = $post->to_date ?: Carbon::today()->format('Y-m-d');
        $role = (int) $post->input('role_id', 0);
        $roles = in_array($role, [4, 5], true) ? [$role] : [4, 5];

        $rows = DB::table('reports as r')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->whereIn('u.role_id', $roles)
            ->whereIn('r.transaction_type', ['Commission', 'Reverse Commission'])
            ->where('r.status', 'Success')
            ->whereBetween('r.created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->groupBy('u.id', 'u.outlet_name', 'u.first_name', 'u.mobile_number', 'u.role_id')
            ->orderBy('u.role_id')
            ->orderByRaw('net_commission DESC')
            ->select(
                'u.id',
                'u.outlet_name',
                'u.first_name',
                'u.mobile_number',
                'u.role_id',
                DB::raw("SUM(CASE WHEN r.transaction_type = 'Commission' THEN r.amount ELSE 0 END) as credit_commission"),
                DB::raw("SUM(CASE WHEN r.transaction_type = 'Reverse Commission' THEN r.amount ELSE 0 END) as reverse_commission"),
                DB::raw("SUM(CASE WHEN r.transaction_type = 'Commission' THEN r.amount ELSE 0 END) - SUM(CASE WHEN r.transaction_type = 'Reverse Commission' THEN r.amount ELSE 0 END) as net_commission"),
                DB::raw("SUM(CASE WHEN r.transaction_type = 'Commission' THEN COALESCE(r.total_amount, 0) ELSE 0 END) as recharge_amount"),
                DB::raw("SUM(CASE WHEN r.transaction_type = 'Commission' THEN 1 ELSE 0 END) as credit_count")
            )
            ->get();

        $dtTotal = 0.0;
        $mdTotal = 0.0;
        $dtAmount = 0.0;
        $mdAmount = 0.0;
        $list = [];
        foreach ($rows as $row) {
            $net = round((float) $row->net_commission, 2);
            $rechargeAmount = round((float) $row->recharge_amount, 2);
            if ((int) $row->role_id === 4) {
                $mdTotal += $net;
                $mdAmount += $rechargeAmount;
                $roleName = 'MD';
            } else {
                $dtTotal += $net;
                $dtAmount += $rechargeAmount;
                $roleName = 'DT';
            }
            $list[] = [
                'id' => (int) $row->id,
                'name' => trim((string) ($row->outlet_name ?: $row->first_name ?: 'User')),
                'mobile' => (string) ($row->mobile_number ?: ''),
                'role' => $roleName,
                'count' => (int) $row->credit_count,
                'recharge_amount' => $rechargeAmount,
                'credit' => round((float) $row->credit_commission, 2),
                'reverse' => round((float) $row->reverse_commission, 2),
                'net' => $net,
            ];
        }

        return response()->json([
            'type' => 'success',
            'from_date' => $from,
            'to_date' => $to,
            'dt_total' => round($dtTotal, 2),
            'md_total' => round($mdTotal, 2),
            'dt_amount' => round($dtAmount, 2),
            'md_amount' => round($mdAmount, 2),
            'total' => round($dtTotal + $mdTotal, 2),
            'rows' => $list,
        ]);
    }
}
