<?php

use App\Jobs\RunMonitorCheck;
use App\Models\Monitoring\Monitor;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Monitor::query()
        ->where('enabled', true)
        ->where('next_check_at', '<=', now())
        ->each(function (Monitor $monitor) {
            RunMonitorCheck::dispatch(
                $monitor->toProbeSnapshot(),
                (string) Str::uuid(),
            );

            $monitor->update([
                'next_check_at' => now()->addSeconds($monitor->interval_seconds),
            ]);
        });
})->everyFifteenSeconds();
