<?php

use App\Services\Monitoring\MonitorDispatcher;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => app(MonitorDispatcher::class)->dispatchDue())
    ->everyFifteenSeconds();

Schedule::command("monitors:prune-results --days=14")->daily();
