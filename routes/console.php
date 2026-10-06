<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('commerce:backup')
    ->dailyAt((string) config('commerce_maintenance.backup.schedule_time', '02:30'))
    ->withoutOverlapping(60)
    ->onOneServer()
    ->environments(['production']);
