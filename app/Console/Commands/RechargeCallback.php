<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use helpers;

class RechargeCallback extends Command
{
    protected $signature = 'recharge_callback';

    protected $description = 'Recharge Call Back Hit';

    public function handle()
    {
        $pendingStatuses = helpers::rechargePendingStatuses();
        $res = DB::table('reports')
            ->whereRaw('LOWER(path) = ?', ['api'])
            ->whereIn('transaction_type', ['Recharge', 'Bill Pay', 'Bill Payment'])
            ->where(function ($q) {
                $q->where('callback_status', 0)->orWhereNull('callback_status');
            })
            ->whereNotIn('status', $pendingStatuses)
            ->orderBy('id')
            ->take(100)
            ->get();

        foreach ($res as $row) {
            try {
                helpers::sendApiPartnerRechargeCallback($row);
            } catch (\Throwable $e) {
            }
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('reports', 'api_partner_call_back_url')) {
            $stuck = DB::table('reports')
                ->whereRaw('LOWER(path) = ?', ['api'])
                ->whereIn('transaction_type', ['Recharge', 'Bill Pay', 'Bill Payment'])
                ->where('callback_status', 1)
                ->where(function ($q) {
                    $q->whereNull('api_partner_call_back_url')->orWhere('api_partner_call_back_url', '');
                })
                ->whereNotIn('status', $pendingStatuses)
                ->where('created_at', '>=', now()->subDays(7))
                ->orderByDesc('id')
                ->take(30)
                ->get();
            foreach ($stuck as $row) {
                try {
                    helpers::sendApiPartnerRechargeCallback($row, true);
                } catch (\Throwable $e) {
                }
            }
        }

        return 0;
    }
}
