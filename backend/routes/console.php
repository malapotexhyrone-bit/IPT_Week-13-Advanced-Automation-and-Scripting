<?php

use App\Services\ClearanceAutomationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('clearance:automation-run', function () {
    $count = app(ClearanceAutomationService::class)->scanAndDispatch();
    $this->info("Dispatched {$count} unique automation delivery record(s).");
})->purpose('Create and dispatch due clearance reminders, escalations, and completion notifications');

Schedule::command('clearance:automation-run')->everyMinute()->withoutOverlapping();
