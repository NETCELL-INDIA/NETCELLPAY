<?php

namespace App\Services;

class UserAppCampaignDispatcher
{
    /** @var array<int, int> */
    public static array $testCalls = [];

    /** @var array<int, int> */
    public static array $testRetryIds = [];

    public function send(int $campaignId, bool $retry = false): array
    {
        if (app()->runningUnitTests()) {
            self::$testCalls[] = $campaignId;
            if ($retry) {
                self::$testRetryIds[] = $campaignId;
            }

            return ['ok' => true, 'output' => ''];
        }

        $root = dirname(base_path());
        $artisan = $root.DIRECTORY_SEPARATOR.'artisan';
        if (! is_file($artisan)) {
            return ['ok' => false, 'output' => 'User app artisan was not found. The campaign was not sent.'];
        }

        $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg($artisan).' notifications:send-campaign '.((int) $campaignId);
        if ($retry) {
            $command .= ' --retry';
        }
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptors, $pipes, $root);
        if (! is_resource($process)) {
            return ['ok' => false, 'output' => 'The campaign command could not be started.'];
        }
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        $text = $this->safeOutput(trim((string) $output."\n".(string) $error));

        return [
            'ok' => $exit === 0,
            'output' => $text,
        ];
    }

    private function safeOutput(string $text): string
    {
        $text = preg_replace('/-----BEGIN PRIVATE KEY-----.*?-----END PRIVATE KEY-----/s', '[redacted]', $text) ?? $text;
        $text = preg_replace('/Bearer\s+[A-Za-z0-9._\-]+/', 'Bearer [redacted]', $text) ?? $text;
        $text = preg_replace('/[A-Za-z0-9:_\-]{80,}/', '[redacted]', $text) ?? $text;

        return mb_substr(trim($text), 0, 400);
    }
}
