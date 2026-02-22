<?php

use App\Models\SiteSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled database backup
Schedule::command('backup:database')
    ->dailyAt(SiteSetting::get('backup_time', '02:00'))
    ->when(function () {
        return SiteSetting::get('backup_enabled', '0') === '1';
    })
    ->skip(function () {
        $frequency = SiteSetting::get('backup_frequency', 'daily');
        if ($frequency === 'weekly' && date('w') !== '0') {
            return true; // Skip unless Sunday
        }
        if ($frequency === 'monthly' && date('j') !== '1') {
            return true; // Skip unless 1st of month
        }
        return false;
    });
