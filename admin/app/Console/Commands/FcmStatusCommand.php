<?php

namespace App\Console\Commands;

use App\Services\FcmHttpV1Service;
use Illuminate\Console\Command;

class FcmStatusCommand extends Command
{
    protected $signature = 'fcm:status {--auth : Also verify OAuth token with Google}';

    protected $description = 'Show Firebase FCM HTTP v1 configuration status (never prints secrets)';

    public function handle(): int
    {
        $status = FcmHttpV1Service::status();
        $this->line('project_id: '.$status['project_id']);
        $this->line('configured: '.($status['configured'] ? 'yes' : 'no'));
        $this->line('safe_path: '.($status['safe_path'] ? 'yes' : 'no'));
        $this->line('client_email_present: '.($status['client_email_present'] ? 'yes' : 'no'));
        $this->line('message: '.$status['message']);

        if ($this->option('auth')) {
            $ok = FcmHttpV1Service::verifyAuth();
            $this->line('oauth: '.($ok ? 'ok' : 'failed'));
            if (! $ok && FcmHttpV1Service::lastError()) {
                $this->line('oauth_error: '.FcmHttpV1Service::lastError());
            }
        }

        return $status['configured'] ? self::SUCCESS : self::FAILURE;
    }
}
