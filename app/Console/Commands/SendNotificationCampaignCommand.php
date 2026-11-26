<?php

namespace App\Console\Commands;

use App\Jobs\SendNotificationCampaign;
use Illuminate\Console\Command;

class SendNotificationCampaignCommand extends Command
{
    protected $signature = 'notifications:send-campaign {campaignId} {--retry : Retry failed recipients only}';

    protected $description = 'Queue one notification campaign. With QUEUE_CONNECTION=sync this runs in the current process.';

    public function handle(): int
    {
        $campaignId = (int) $this->argument('campaignId');
        if ($campaignId <= 0) {
            $this->error('Campaign id is required.');

            return 1;
        }

        SendNotificationCampaign::dispatch($campaignId, (bool) $this->option('retry'));
        $driver = (string) config('queue.default');
        if ($driver === 'sync') {
            $this->warn('QUEUE_CONNECTION=sync. Delivery ran in this process. It is not a background worker.');
        } else {
            $this->info('Campaign queued on the '.$driver.' connection. A worker must be running before it is delivered.');
        }

        return 0;
    }
}
