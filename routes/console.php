<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('simlogo:retry-pending')
    ->dailyAt('10:20')
    ->runInBackground();

Schedule::command('simlogo:check-status')
    ->hourly()
    ->runInBackground();
