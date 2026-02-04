<?php

use Illuminate\Support\Facades\Schedule;

// 1. The Nightly 1 AM Scheduler
Schedule::command('simlogo:retry-pending')
    ->everyFifteenMinutes();

// 2. The Hourly Scheduler
Schedule::command('simlogo:check-status')
    ->everyFiveMinutes();
