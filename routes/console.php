<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
use Illuminate\Support\Facades\Schedule;

Schedule::command('sla:check')->hourly()->withoutOverlapping(30)->onOneServer();
Schedule::command('invoice:check-due')->dailyAt('08:00');
