<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Automatically clean old backups every day at 1:00 AM
// Schedule::command('backup:clean')->daily()->at('01:00');

// Automatically take a new backup every day at 2:00 AM
// Schedule::command('backup:run')->daily()->at('02:00');
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::command('model:prune')->daily();