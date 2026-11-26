<?php

namespace App\Console\Commands;

use App\Jobs\SendNotificationCampaign;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DispatchDueNotificationCampaigns extends Command
{
    protected $signature = 'notifications:dispatch-due';

    protected $description = 'Send notification campaigns whose schedule time has arrived.';

    public function handle(): int
    {
        if (! Schema::hasTable('notification_campaigns')) {
            $this->warn('notification_campaigns is not installed.');

            return 0;
        }

        $due = DB::table('notification_campaigns')
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', Carbon::now())
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
            })
            ->orderBy('id')
            ->limit(10)
            ->pluck('id');

        $started = 0;
        foreach ($due as $id) {
            $claimed = DB::table('notification_campaigns')
                ->where('id', $id)
                ->where('status', 'scheduled')
                ->update([
                    'status' => 'processing',
                    'updated_at' => Carbon::now(),
                ]);
            if ($claimed !== 1) {
                continue;
            }
            try {
                SendNotificationCampaign::dispatch((int) $id, false);
                $started++;
            } catch (\Throwable $e) {
                DB::table('notification_campaigns')
                    ->where('id', $id)
                    ->where('status', 'processing')
                    ->whereNull('started_at')
                    ->update([
                        'status' => 'scheduled',
                        'updated_at' => Carbon::now(),
                    ]);
                $this->error('Campaign '.$id.' was left scheduled.');
            }
        }

        $this->info('Due campaigns started: '.$started);

        return 0;
    }
}
