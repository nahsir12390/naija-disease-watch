<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:sync-ncdc-reports')->dailyAt('06:00')->timezone('Africa/Lagos')->withoutOverlapping();
