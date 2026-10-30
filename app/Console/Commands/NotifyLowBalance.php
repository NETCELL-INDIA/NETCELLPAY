<?php

namespace App\Console\Commands;

use App\Services\RetailerNoticeService;
use Illuminate\Console\Command;

class NotifyLowBalance extends Command
{
    protected $signature = 'notify_low_balance';

    protected $description = 'Send one WhatsApp a day to retailers whose wallet is below the alert limit';

    public function handle(): int
    {
        $count = RetailerNoticeService::scanLowBalances();
        $this->info('Low balance check queued for '.$count.' retailer(s).');

        return 0;
    }
}
