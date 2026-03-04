<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('simlogo:retry-pending')
    ->dailyAt('01:30')
    ->runInBackground();

Schedule::command('simlogo:check-status')
    ->hourly()
    ->runInBackground();
