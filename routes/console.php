<?php

use App\Jobs\RunMonitorCheck;
use App\Models\Monitoring\Monitor;
use App\Models\Scopes\TenantScope;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    // Scheduler has no authenticated user; bypass tenant scope explicitly.
    Monitor::query()
        ->withoutGlobalScope(TenantScope::class)
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
