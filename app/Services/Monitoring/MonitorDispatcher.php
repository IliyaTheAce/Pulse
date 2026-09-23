<?php

namespace App\Services\Monitoring;

use App\Jobs\RunMonitorCheck;
use App\Models\Monitoring\Monitor;
use App\Models\Scopes\TenantScope;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MonitorDispatcher
{
    /**
     * Enqueue due monitors. Per-monitor Redis (or cache) locks prevent
     * two ticks / two schedulers from double-enqueuing the same monitor.
     */
    public function dispatchDue(): void
    {
        // Scheduler has no authenticated user; bypass tenant scope explicitly.
        Monitor::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('enabled', true)
            ->where('next_check_at', '<=', now())
            ->each(function (Monitor $monitor) {
                $lock = Cache::lock("monitor:dispatch:{$monitor->id}", 10);

                if (! $lock->get()) {
                    return;
                }

                try {
                    RunMonitorCheck::dispatch(
                        $monitor->toProbeSnapshot(),
                        (string) Str::uuid(),
                    );

                    $monitor->update([
                        'next_check_at' => now()->addSeconds($monitor->interval_seconds),
                    ]);
                } finally {
                    $lock->release();
                }
            });
    }
}
