<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BackupEmail;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SuperAdminController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();
        $currentTheme = SiteSetting::getTheme();

        $backupSettings = [
            'enabled' => SiteSetting::get('backup_enabled', '0'),
            'frequency' => SiteSetting::get('backup_frequency', 'daily'),
            'time' => SiteSetting::get('backup_time', '02:00'),
            'retention' => SiteSetting::get('backup_retention', '10'),
            'email_enabled' => SiteSetting::get('backup_email_enabled', '0'),
            'email_recipients' => SiteSetting::get('backup_email_recipients', ''),
            'gdrive_enabled' => SiteSetting::get('backup_gdrive_enabled', '0'),
            'gdrive_folder_id' => SiteSetting::get('backup_gdrive_folder_id', ''),
            'gdrive_configured' => !empty(config('services.google.client_id'))
                && !empty(config('services.google.client_secret'))
                && !empty(config('services.google.drive_refresh_token')),
        ];

        $backups = $this->getBackupFiles();

        $siteSettings = [
            'registration_enabled' => SiteSetting::get('registration_enabled', '1'),
            'player_score_posting_enabled' => SiteSetting::get('player_score_posting_enabled', '1'),
        ];

        return view('admin.super', compact('users', 'currentTheme', 'backupSettings', 'backups', 'siteSettings'));
    }

    public function backup()
    {
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $filename = $database . '_backup_' . date('Y-m-d_His') . '.sql';

        return new StreamedResponse(function () use ($host, $port, $database, $username, $password) {
            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database)
            );

            $process = popen($command, 'r');
            if ($process) {
                while (!feof($process)) {
                    echo fread($process, 8192);
                    flush();
                }
                pclose($process);
            }
        }, 200, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function restore(Request $request)
    {
        $request->validate([
            'sql_file' => 'required|file|max:102400',
        ]);

        $file = $request->file('sql_file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, ['sql'])) {
            return redirect()->route('admin.super.index')->with('error', 'Only .sql files are allowed.');
        }

        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $tmpPath = $file->getRealPath();

        $command = sprintf(
            'mysql --host=%s --port=%s --user=%s --password=%s %s < %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($tmpPath)
        );

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            return redirect()->route('admin.super.index')->with('error', 'Restore failed: ' . implode("\n", $output));
        }

        return redirect()->route('admin.super.index')->with('success', 'Database restored successfully from ' . $file->getClientOriginalName());
    }

    public function updateBackupSchedule(Request $request)
    {
        $validated = $request->validate([
            'backup_enabled' => 'required|in:0,1',
            'backup_frequency' => 'required|in:daily,weekly,monthly',
            'backup_time' => 'required|date_format:H:i',
            'backup_retention' => 'required|integer|min:1|max:50',
        ]);

        SiteSetting::set('backup_enabled', $validated['backup_enabled']);
        SiteSetting::set('backup_frequency', $validated['backup_frequency']);
        SiteSetting::set('backup_time', $validated['backup_time']);
        SiteSetting::set('backup_retention', (string) $validated['backup_retention']);

        return redirect()->route('admin.super.index')->with('success', 'Backup schedule settings saved.');
    }

    public function runBackupNow()
    {
        $exitCode = Artisan::call('backup:database');

        if ($exitCode === 0) {
            return redirect()->route('admin.super.index')->with('success', 'Backup created successfully.');
        }

        return redirect()->route('admin.super.index')->with('error', 'Backup failed. Check the server logs for details.');
    }

    public function downloadBackup($filename)
    {
        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);
        $filepath = storage_path('app/backups/' . $filename);

        if (!file_exists($filepath) || !str_ends_with($filename, '.sql')) {
            return redirect()->route('admin.super.index')->with('error', 'Backup file not found.');
        }

        return response()->download($filepath, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    public function deleteBackup($filename)
    {
        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);
        $filepath = storage_path('app/backups/' . $filename);

        if (!file_exists($filepath) || !str_ends_with($filename, '.sql')) {
            return redirect()->route('admin.super.index')->with('error', 'Backup file not found.');
        }

        unlink($filepath);

        return redirect()->route('admin.super.index')->with('success', 'Backup file deleted.');
    }

    protected function getBackupFiles(): array
    {
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            return [];
        }

        $files = glob($backupDir . '/*.sql');
        if (empty($files)) {
            return [];
        }

        // Sort newest first
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        return array_map(function ($file) {
            return [
                'name' => basename($file),
                'size' => $this->formatFileSize(filesize($file)),
                'date' => date('M j, Y g:i A', filemtime($file)),
            ];
        }, $files);
    }

    protected function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    public function updateUserRole(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Prevent modifying own role
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.super.index')->with('error', 'You cannot change your own role.');
        }

        $validated = $request->validate([
            'role' => 'required|in:user,admin,super_admin',
        ]);

        $user->is_admin = in_array($validated['role'], ['admin', 'super_admin']);
        $user->is_super_admin = $validated['role'] === 'super_admin';
        $user->save();

        $roleLabel = match($validated['role']) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            default => 'User',
        };

        return redirect()->route('admin.super.index')->with('success', $user->name . ' is now ' . $roleLabel . '.');
    }

    public function resetUserPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Prevent resetting own password (use profile page instead)
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.super.index')
                ->with('error', 'You cannot reset your own password here. Use your profile page.');
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->password = Hash::make($validated['password']);
        $user->setRememberToken(Str::random(60));
        $user->save();

        return redirect()->route('admin.super.index')
            ->with('success', 'Password for ' . $user->name . ' has been reset.');
    }

    public function updateBackupDelivery(Request $request)
    {
        $validated = $request->validate([
            'backup_email_enabled' => 'required|in:0,1',
            'backup_email_recipients' => 'nullable|string|max:1000',
            'backup_gdrive_enabled' => 'required|in:0,1',
            'backup_gdrive_folder_id' => 'nullable|string|max:255',
        ]);

        // Validate each email address if provided
        if (!empty($validated['backup_email_recipients'])) {
            $emails = array_map('trim', explode(',', $validated['backup_email_recipients']));
            foreach ($emails as $email) {
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return redirect()->route('admin.super.index')
                        ->with('error', "Invalid email address: {$email}");
                }
            }
        }

        // Prevent enabling Google Drive without OAuth credentials
        if ($validated['backup_gdrive_enabled'] === '1'
            && (empty(config('services.google.client_id'))
                || empty(config('services.google.client_secret'))
                || empty(config('services.google.drive_refresh_token')))) {
            return redirect()->route('admin.super.index')
                ->with('error', 'Cannot enable Google Drive backup: GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_DRIVE_REFRESH_TOKEN must be set in .env. Run: php artisan google:auth');
        }

        SiteSetting::set('backup_email_enabled', $validated['backup_email_enabled']);
        SiteSetting::set('backup_email_recipients', $validated['backup_email_recipients'] ?? '');
        SiteSetting::set('backup_gdrive_enabled', $validated['backup_gdrive_enabled']);
        SiteSetting::set('backup_gdrive_folder_id', $validated['backup_gdrive_folder_id'] ?? '');

        return redirect()->route('admin.super.index')
            ->with('success', 'Backup delivery settings saved.');
    }

    public function testBackupEmail()
    {
        $recipients = SiteSetting::get('backup_email_recipients', '');
        $emails = array_filter(array_map('trim', explode(',', $recipients)));

        if (empty($emails)) {
            return redirect()->route('admin.super.index')
                ->with('error', 'No email recipients configured. Save delivery settings first.');
        }

        $backupDir = storage_path('app/backups');
        $files = glob($backupDir . '/*_backup_*.sql');

        if (empty($files)) {
            return redirect()->route('admin.super.index')
                ->with('error', 'No backup files exist. Run a backup first, then test email.');
        }

        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
        $latestFile = $files[0];

        try {
            Mail::to($emails)->send(new BackupEmail($latestFile, basename($latestFile)));
            return redirect()->route('admin.super.index')
                ->with('success', 'Test email sent to: ' . implode(', ', $emails));
        } catch (\Exception $e) {
            return redirect()->route('admin.super.index')
                ->with('error', 'Test email failed: ' . $e->getMessage());
        }
    }

    public function uploadGdriveCredentials(Request $request)
    {
        $request->validate([
            'gdrive_credentials' => 'required|file|max:50',
        ]);

        $file = $request->file('gdrive_credentials');

        if (strtolower($file->getClientOriginalExtension()) !== 'p12') {
            return redirect()->route('admin.super.index')
                ->with('error', 'Only .p12 files are accepted.');
        }

        $contents = file_get_contents($file->getRealPath());

        // Validate the P12 file can be read with the default Google password
        $certs = [];
        if (!openssl_pkcs12_read($contents, $certs, 'notasecret')) {
            return redirect()->route('admin.super.index')
                ->with('error', 'Invalid P12 file: could not read with the default Google service account password.');
        }

        if (empty($certs['pkey'])) {
            return redirect()->route('admin.super.index')
                ->with('error', 'Invalid P12 file: no private key found.');
        }

        $dir = storage_path('app/private/google');
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        file_put_contents($dir . '/service-account.p12', $contents);
        chmod($dir . '/service-account.p12', 0600);

        return redirect()->route('admin.super.index')
            ->with('success', 'Google Drive P12 key uploaded successfully.');
    }

    public function deleteGdriveCredentials()
    {
        SiteSetting::set('backup_gdrive_enabled', '0');

        $p12Path = storage_path('app/private/google/service-account.p12');
        if (file_exists($p12Path)) {
            unlink($p12Path);
        }

        // Also clean up legacy JSON key if present
        $jsonPath = storage_path('app/private/google/service-account.json');
        if (file_exists($jsonPath)) {
            unlink($jsonPath);
        }

        return redirect()->route('admin.super.index')
            ->with('success', 'Google Drive credentials removed and backup disabled.');
    }

    public function testGdriveConnection()
    {
        $folderId = SiteSetting::get('backup_gdrive_folder_id', '');

        if (empty($folderId)) {
            return redirect()->route('admin.super.index')
                ->with('error', 'No Google Drive folder ID configured. Save delivery settings first.');
        }

        try {
            $service = new GoogleDriveService();
            $result = $service->testConnection($folderId);

            if ($result['success']) {
                return redirect()->route('admin.super.index')
                    ->with('success', 'Google Drive connection successful! ' . $result['message']);
            }

            return redirect()->route('admin.super.index')
                ->with('error', 'Google Drive test failed: ' . $result['message']);
        } catch (\Exception $e) {
            return redirect()->route('admin.super.index')
                ->with('error', 'Google Drive test failed: ' . $e->getMessage());
        }
    }

    public function updateTheme(Request $request)
    {
        $validated = $request->validate([
            'theme_name' => 'required|string|max:50',
            'primary_color' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'secondary_color' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        SiteSetting::set('theme_name', $validated['theme_name']);
        SiteSetting::set('theme_primary_color', $validated['primary_color']);
        SiteSetting::set('theme_secondary_color', $validated['secondary_color']);

        return redirect()->route('admin.super.index')->with('success', 'Site theme updated successfully.');
    }

    public function updateSiteSettings(Request $request)
    {
        SiteSetting::set('registration_enabled', $request->input('registration_enabled', '0'));
        SiteSetting::set('player_score_posting_enabled', $request->input('player_score_posting_enabled', '0'));

        return redirect()->route('admin.super.index')->with('success', 'Site settings updated successfully.');
    }
}
