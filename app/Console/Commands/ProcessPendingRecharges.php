<?php

namespace App\Console\Commands;

use App\Jobs\ProcessRecharge;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessPendingRecharges extends Command
{
    protected $signature = 'process_pending_recharges';

    protected $description = 'Process stuck Pending recharge reports (Hostinger shutdown fallback backup)';

    public function handle(): int
    {
        $rows = DB::table('reports')
            ->whereIn('transaction_type', ['Recharge', 'Bill Pay'])
            ->where('status', 'Pending')
            ->where('created_at', '<=', Carbon::now()->subSeconds(20))
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->whereNotNull('api_id')
            ->where('api_id', '>', 0)
            ->orderBy('id')
            ->limit(25)
            ->get(['id', 'api_id', 'provider_id', 'transaction_type']);

        foreach ($rows as $row) {
            if (!$row->provider_id) {
                continue;
            }

            $service = $row->transaction_type === 'Bill Pay' ? 'Bill Pay' : 'Recharge';

            try {
                (new ProcessRecharge($row->api_id, $row->provider_id, $row->id, $service))->handle();
            } catch (\Throwable $e) {
                try {
                    DB::table('apilogs')->insert([
                        'url' => 'process_pending_recharges',
                        'modal' => 'ProcessRecharge',
                        'txnid' => (string) $row->id,
                        'header' => json_encode([]),
                        'request' => json_encode(['report_id' => $row->id]),
                        'response' => 'cron backup failed',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                } catch (\Throwable $ignored) {
                }
            }
        }

        return 0;
    }
}
