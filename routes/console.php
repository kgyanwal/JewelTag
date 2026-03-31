<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Automatically clean old backups every day at 1:00 AM
// Schedule::command('backup:clean')->daily()->at('01:00');
// Backup all tenants daily at 2:00 AM
// Schedule::command('backup:tenants --tenant=lxd')->daily()->at('02:00');
// Schedule::command('backup:tenants --tenant=thedsq')->daily()->at('02:05');



// ── CSV APPEND EXPORT (daily data, readable format)
Schedule::command('export:tenant-csv --tenant=lxd')->daily()->at('03:00');
Schedule::command('export:tenant-csv --tenant=thedsq')->daily()->at('03:05');

// Optional: also keep central DB backup
Schedule::command('backup:run')->daily()->at('02:30');
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::command('model:prune')->daily();