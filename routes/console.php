<?php

use App\Jobs\CreateDailySnapshotsJob;
use App\Jobs\SyncCircuitAggregatesJob;
use App\Jobs\SyncCircuitsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Sync Schedule Configuration
|--------------------------------------------------------------------------
|
| These scheduled jobs sync data from the WorkStudio API. All times are
| in Eastern Time (America/New_York) and run on weekdays only.
|
| ACTIV circuits: Twice daily (4:30 AM & 4:30 PM ET)
| QC/REWORK/CLOSE circuits: Weekly on Monday (4:30 AM ET)
| Daily snapshots: 5:00 AM ET (after syncs complete)
| Aggregate sync: 5:30 AM ET (after circuit sync)
|
*/

// ACTIV circuits - twice daily on weekdays
Schedule::job(new SyncCircuitsJob(['ACTIV']))
    ->weekdays()
    ->timezone('America/New_York')
    ->at('04:30')
    ->name('sync-circuits-activ-morning')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::job(new SyncCircuitsJob(['ACTIV']))
    ->weekdays()
    ->timezone('America/New_York')
    ->at('16:30')
    ->name('sync-circuits-activ-afternoon')
    ->withoutOverlapping()
    ->onOneServer();

// QC, REWORK, CLOSE - weekly on Monday
Schedule::job(new SyncCircuitsJob(['QC', 'REWRK', 'CLOSE']))
    ->mondays()
    ->timezone('America/New_York')
    ->at('04:30')
    ->name('sync-circuits-weekly')
    ->withoutOverlapping()
    ->onOneServer();

// Aggregate sync - after circuit sync
Schedule::job(new SyncCircuitAggregatesJob(['ACTIV']))
    ->weekdays()
    ->timezone('America/New_York')
    ->at('05:30')
    ->name('sync-aggregates-morning')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::job(new SyncCircuitAggregatesJob(['ACTIV']))
    ->weekdays()
    ->timezone('America/New_York')
    ->at('17:30')
    ->name('sync-aggregates-afternoon')
    ->withoutOverlapping()
    ->onOneServer();

// Daily snapshots - 5:00 AM ET (after syncs complete)
Schedule::job(new CreateDailySnapshotsJob)
    ->weekdays()
    ->timezone('America/New_York')
    ->at('05:00')
    ->name('create-daily-snapshots')
    ->withoutOverlapping()
    ->onOneServer();
