<?php

namespace App\Console\Commands;

use App\Mail\BackupEmail;
use App\Models\SiteSetting;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database';
    protected $description = 'Create a scheduled backup of the database';

    public function handle()
    {
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $filename = $database . '_backup_' . date('Y-m-d_His') . '.sql';
        $filepath = $backupDir . '/' . $filename;

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            $error = implode("\n", $output);
            Log::error("Database backup failed: {$error}");
            $this->error("Backup failed: {$error}");
            // Clean up empty/partial file
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            return Command::FAILURE;
        }

        Log::info("Database backup created: {$filename}");
        $this->info("Backup created: {$filename}");

        // Deliver backup via email and/or Google Drive
        $this->deliverViaEmail($filepath, $filename);
        $this->deliverViaGoogleDrive($filepath, $filename);

        // Clean up old backups based on retention setting
        $this->cleanOldBackups($backupDir);

        return Command::SUCCESS;
    }

    protected function deliverViaEmail(string $filepath, string $filename): void
    {
        if (SiteSetting::get('backup_email_enabled', '0') !== '1') {
            return;
        }

        $recipientsCsv = SiteSetting::get('backup_email_recipients', '');
        $recipients = array_filter(array_map('trim', explode(',', $recipientsCsv)));

        if (empty($recipients)) {
            Log::warning('Backup email delivery enabled but no recipients configured.');
            return;
        }

        try {
            Mail::to($recipients)->send(new BackupEmail($filepath, $filename));
            Log::info('Backup emailed to: ' . implode(', ', $recipients));
            $this->info('Backup emailed to ' . count($recipients) . ' recipient(s).');
        } catch (\Exception $e) {
            Log::error('Backup email delivery failed: ' . $e->getMessage());
            $this->error('Email delivery failed: ' . $e->getMessage());
        }
    }

    protected function deliverViaGoogleDrive(string $filepath, string $filename): void
    {
        if (SiteSetting::get('backup_gdrive_enabled', '0') !== '1') {
            return;
        }

        $folderId = SiteSetting::get('backup_gdrive_folder_id', '');
        if (empty($folderId)) {
            Log::warning('Google Drive delivery enabled but no folder ID configured.');
            return;
        }

        try {
            $service = new GoogleDriveService();
            $fileId = $service->uploadFile($filepath, $filename, $folderId);
            Log::info("Backup uploaded to Google Drive: {$fileId}");
            $this->info('Backup uploaded to Google Drive.');
        } catch (\Exception $e) {
            Log::error('Google Drive delivery failed: ' . $e->getMessage());
            $this->error('Google Drive delivery failed: ' . $e->getMessage());
        }
    }

    protected function cleanOldBackups(string $backupDir): void
    {
        $retention = (int) SiteSetting::get('backup_retention', 10);
        if ($retention <= 0) {
            return;
        }

        $files = glob($backupDir . '/*_backup_*.sql');
        if (count($files) <= $retention) {
            return;
        }

        // Sort by modification time, oldest first
        usort($files, fn($a, $b) => filemtime($a) - filemtime($b));

        $toDelete = array_slice($files, 0, count($files) - $retention);
        foreach ($toDelete as $file) {
            unlink($file);
            Log::info("Old backup removed: " . basename($file));
        }

        $this->info("Cleaned up " . count($toDelete) . " old backup(s).");
    }
}
