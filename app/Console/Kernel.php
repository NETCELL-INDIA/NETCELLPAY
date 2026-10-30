<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */

     protected $commands = [

        'App\Console\Commands\RechargeCallback',
        'App\Console\Commands\ComplaintCallback',
        'App\Console\Commands\SendSms',
        'App\Console\Commands\ProcessPendingRecharges',
        'App\Console\Commands\CheckPendingRechargeStatus',
        'App\Console\Commands\NotifyLowBalance',
        'App\Console\Commands\SendDailySalesSummary',
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('recharge_callback')->everyMinute();
        $schedule->command('complaint_callback')->everyMinute();
        $schedule->command('send_sms_every_minutes')->everyMinute();
        $schedule->command('process_pending_recharges')->everyMinute();
        $schedule->command('check_pending_recharge_status')->everyMinute();
        $schedule->command('notify_low_balance')->everyFifteenMinutes();
        $schedule->command('send_daily_sales_summary')->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
