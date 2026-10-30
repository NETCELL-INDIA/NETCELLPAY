<?php

namespace App\Console\Commands;

use App\Services\PendingRechargeStatusService;
use Illuminate\Console\Command;

class CheckPendingRechargeStatus extends Command
{
    protected $signature = 'check_pending_recharge_status';

    protected $description = 'Ask supplier status APIs about pending recharges without sending the recharge again';

    public function handle(): int
    {
        $rows = PendingRechargeStatusService::checkDue(15);
        $done = 0;
        foreach ($rows as $row) {
            if (! empty($row['ok']) && ($row['status'] ?? '') !== 'Pending') {
                $done++;
            }
        }
        $this->info('Checked '.count($rows).' pending recharge(s); '.$done.' updated.');

        return 0;
    }
}
