<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedDefaultSmsTemplates extends Command
{
    protected $signature = 'db:seed-sms-templates {--force : Insert even if templates exist}';

    protected $description = 'Seed default SMS/WhatsApp templates for OTP, user create, and fund messages';

    public function handle(): int
    {
        $templates = [
            [
                'slug' => 'otp',
                'template_id' => 'OTP_VERIFY',
                'content' => 'Dear {NAME}, Your OTP is {OTP}. Do not share it with anyone. - NETCELL PAY',
            ],
            [
                'slug' => 'create_user',
                'template_id' => 'CREATE_USER',
                'content' => 'Dear {NAME}, Welcome! Login Mobile: {MOBILE}, Password: {PASSWORD}, PIN: {PIN}. - NETCELL PAY',
            ],
            [
                'slug' => 'fund_receive',
                'template_id' => 'FUND_RECEIVE',
                'content' => 'Dear {NAME}, Your wallet has been {TYPE} with Rs.{AMOUNT} by {BY}. Balance: Rs.{CURRENT_BALANCE}. - NETCELL PAY',
            ],
            [
                'slug' => 'fund_reverse',
                'template_id' => 'FUND_REVERSE',
                'content' => 'Dear {NAME}, Your wallet has been {TYPE} with Rs.{AMOUNT} by {BY}. Balance: Rs.{CURRENT_BALANCE}. - NETCELL PAY',
            ],
            [
                'slug' => 'forgot_password',
                'template_id' => 'FORGOT_PASSWORD',
                'content' => 'Dear {NAME}, Your new password is {PASSWORD}. Mobile: {MOBILE}, PIN: {PIN}. - NETCELL PAY',
            ],
        ];

        $now = Carbon::now();
        $inserted = 0;
        $skipped = 0;

        foreach ($templates as $template) {
            $exists = DB::table('sms_templates')->where('slug', $template['slug'])->exists();
            if ($exists && !$this->option('force')) {
                $skipped++;
                continue;
            }

            if ($exists) {
                DB::table('sms_templates')->where('slug', $template['slug'])->update([
                    'template_id' => $template['template_id'],
                    'content' => $template['content'],
                    'status' => 1,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('sms_templates')->insert([
                    'slug' => $template['slug'],
                    'template_id' => $template['template_id'],
                    'content' => $template['content'],
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $inserted++;
        }

        $this->info("SMS templates ready. Updated/inserted: {$inserted}, skipped: {$skipped}.");
        return self::SUCCESS;
    }
}
