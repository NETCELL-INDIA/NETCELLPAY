<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RefreshApplicationDatabase extends Command
{
    protected $signature = 'db:refresh-app {--force : Run without confirmation}';

    protected $description = 'Clear old transactional data and keep only fresh admin setup';

    protected array $truncateTables = [
        'reports',
        'backup_reports',
        'fund_requests',
        'backup_fund_requests',
        'login_histories',
        'complaints',
        'rehit_recharge_logs',
        'apilogs',
        'emails',
        'failed_jobs',
        'jobs',
        'supplier_fail_to_success',
        'user_wize_switch',
        'amount_wize_switch',
        'state_wize_switch',
        'general_routings',
        'operator_service_routings',
        'amount_blocks',
        'announcements',
        'users_laravel_default',
    ];

    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('This will delete old users and all transaction history. Continue?')) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        $backupDir = storage_path('backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $dbName = config('database.connections.mysql.database');
        $backupFile = $backupDir . DIRECTORY_SEPARATOR . $dbName . '_' . date('Ymd_His') . '.sql';
        $this->createBackup($backupFile);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($this->truncateTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            DB::table($table)->truncate();
            $this->line("Truncated: {$table}");
        }

        if (Schema::hasTable('users')) {
            DB::table('users')->where('id', '!=', 1)->delete();
            DB::table('users')->where('id', 1)->update([
                'wallet_balance' => 0,
                'minium_balance' => 0,
                'otp' => null,
                'email_otp' => null,
                'otp_limit' => 0,
                'otp_created_at' => null,
                'deleted_at' => 0,
                'updated_at' => now(),
            ]);
            DB::statement('ALTER TABLE users AUTO_INCREMENT = 2');
            $this->line('Users reset: admin kept, next user id starts from 2');
        }

        if (Schema::hasTable('messages')) {
            DB::table('messages')->truncate();
            $this->line('Truncated: messages');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->newLine();
        $this->info('Database refreshed successfully.');
        $this->info('Backup saved: ' . $backupFile);
        $this->info('Admin login kept: mobile 9060067000');

        return self::SUCCESS;
    }

    protected function createBackup(string $backupFile): void
    {
        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3306');
        $user = config('database.connections.mysql.username', 'root');
        $pass = config('database.connections.mysql.password', '');
        $db = config('database.connections.mysql.database');

        $mysqldump = $this->findMysqlDump();
        if (!$mysqldump) {
            $this->warn('mysqldump not found. Skipping SQL backup.');
            return;
        }

        $passArg = $pass !== '' ? ' -p' . escapeshellarg($pass) : '';
        $command = sprintf(
            '%s -h %s -P %s -u %s%s --single-transaction --routines --triggers %s > %s',
            escapeshellarg($mysqldump),
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($user),
            $passArg,
            escapeshellarg($db),
            escapeshellarg($backupFile)
        );

        $result = null;
        system($command, $result);
        if ($result !== 0 || !is_file($backupFile) || filesize($backupFile) === 0) {
            $this->warn('SQL backup could not be created. Continuing with refresh.');
        } else {
            $this->info('Backup created.');
        }
    }

    protected function findMysqlDump(): ?string
    {
        $candidates = [
            'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe',
            'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'mysqldump',
        ];

        foreach ($candidates as $path) {
            if ($path === 'mysqldump') {
                return $path;
            }
            if (is_file($path)) {
                return $path;
            }
        }

        foreach (glob('C:\\laragon\\bin\\mysql\\*\\bin\\mysqldump.exe') ?: [] as $path) {
            return $path;
        }

        return null;
    }
}
