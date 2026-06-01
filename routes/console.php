<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reminders:dispatch')->everyMinute();
Schedule::command('subscriptions:run')->dailyAt('07:00');
Schedule::command('debts:remind')->dailyAt('08:00');
Schedule::command('recap:send', ['--period=weekly'])->weeklyOn(0, '20:00');
Schedule::command('recap:send', ['--period=monthly'])->lastDayOfMonth('20:00');
