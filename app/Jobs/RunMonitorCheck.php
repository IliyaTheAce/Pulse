<?php

namespace App\Jobs;

use App\Models\Monitoring\Monitor;
use App\Models\Monitoring\MonitorResult;
use App\Services\Monitoring\HttpMonitorChecker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunMonitorCheck implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout;

    /**
     * @param array<string, mixed> $probe
     */
    public function __construct(
        public array  $probe,
        public string $executionId,
    )
    {
        $this->timeout = (int)ceil(($probe['timeout_ms'] ?? 5000) / 1000) + 10;
    }

    /**
     * Execute the job.
     */
    public function handle(HttpMonitorChecker $checker): void
    {
        $result = $checker->check($this->probe);

        MonitorResult::firstOrCreate(
            ['execution_id' => $this->executionId],
            array_merge($result, [
                'monitor_id' => $this->probe['monitor_id'],
                'team_id' => $this->probe['team_id'],
                'checked_at' => now(),
            ])
        );

        Monitor::query()
            ->withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->whereKey($this->probe['monitor_id'])
            ->update(['last_checked_at' => now()]);
    }
}
