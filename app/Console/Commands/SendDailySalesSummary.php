<?php

namespace App\Console\Commands;

use App\Services\RetailerNoticeService;
use Illuminate\Console\Command;

class SendDailySalesSummary extends Command
{
    protected $signature = 'send_daily_sales_summary';

    protected $description = 'Send yesterday sales summary to the admin on WhatsApp once each morning';

    public function handle(): int
    {
        $sent = RetailerNoticeService::sendDailySales();
        $this->info($sent ? 'Daily sales summary sent.' : 'Daily sales summary skipped.');

        return 0;
    }
}
