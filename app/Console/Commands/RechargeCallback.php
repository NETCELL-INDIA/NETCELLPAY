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
        $res = DB::table('reports')
            ->whereRaw('LOWER(path) = ?', ['api'])
            ->where('transaction_type', 'Recharge')
            ->where(function ($q) {
                $q->where('callback_status', 0)->orWhereNull('callback_status');
            })
            ->whereNotIn('status', helpers::rechargePendingStatuses())
            ->orderBy('id')
            ->take(100)
            ->get();

        foreach ($res as $row) {
            try {
                helpers::sendApiPartnerRechargeCallback($row);
            } catch (\Throwable $e) {
            }
        }

        return 0;
    }
}
