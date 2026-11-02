<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Photo files no child points at (TODO #22): once a day, at 3 at night in the church's own timezone.
Schedule::command('nxtgen:photos-sweep')->dailyAt('03:00')->timezone(is_string($tz = config('nxtgen.church.timezone')) && $tz !== '' ? $tz : 'UTC');
