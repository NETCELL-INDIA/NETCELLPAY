<?php

namespace App\Jobs;

use App\Services\NotificationCampaignSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    public int $timeout = 120;

    public function __construct(public int $campaignId, public bool $retryFailed = false)
    {
    }

    public function handle(NotificationCampaignSender $sender): void
    {
        $sender->deliver($this->campaignId, $this->retryFailed, $this->attempts(), $this->tries);
    }

    public function failed(\Throwable $exception): void
    {
        unset($exception);
        app(NotificationCampaignSender::class)->markInterrupted($this->campaignId);
    }
}
