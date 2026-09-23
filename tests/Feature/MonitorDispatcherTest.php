<?php

use App\Jobs\RunMonitorCheck;
use App\Models\Monitoring\Monitor;
use App\Services\Monitoring\MonitorDispatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

it('does not double-enqueue when a per-monitor lock is already held', function () {
    Queue::fake();

    $monitor = Monitor::factory()->create([
        'enabled' => true,
        'interval_seconds' => 60,
        'next_check_at' => now()->subSecond(),
    ]);

    $lock = Cache::lock("monitor:dispatch:{$monitor->id}", 10);
    expect($lock->get())->toBeTrue();

    try {
        app(MonitorDispatcher::class)->dispatchDue();
        Queue::assertNotPushed(RunMonitorCheck::class);
    } finally {
        $lock->release();
    }

    app(MonitorDispatcher::class)->dispatchDue();
    Queue::assertPushed(RunMonitorCheck::class, 1);
});

it('enqueues a due monitor only once across two dispatcher ticks', function () {
    Queue::fake();

    $monitor = Monitor::factory()->create([
        'enabled' => true,
        'interval_seconds' => 60,
        'next_check_at' => now()->subSecond(),
    ]);

    $dispatcher = app(MonitorDispatcher::class);

    $dispatcher->dispatchDue();
    $dispatcher->dispatchDue();

    Queue::assertPushed(RunMonitorCheck::class, 1);

    expect($monitor->fresh()->next_check_at->isFuture())->toBeTrue();
});

it('skips disabled monitors even when next_check_at is due', function () {
    Queue::fake();

    Monitor::factory()->disabled()->create([
        'interval_seconds' => 60,
        'next_check_at' => now()->subMinute(),
    ]);

    app(MonitorDispatcher::class)->dispatchDue();

    Queue::assertNotPushed(RunMonitorCheck::class);
});

it('stops new jobs after disable while a prior job is still queued', function () {
    Queue::fake();

    $monitor = Monitor::factory()->create([
        'enabled' => true,
        'interval_seconds' => 60,
        'next_check_at' => now()->subSecond(),
    ]);

    $dispatcher = app(MonitorDispatcher::class);

    $dispatcher->dispatchDue();
    Queue::assertPushed(RunMonitorCheck::class, 1);

    // Simulate: worker still has the job; monitor becomes due again but is disabled.
    $monitor->update([
        'enabled' => false,
        'next_check_at' => now()->subSecond(),
    ]);

    $dispatcher->dispatchDue();

    Queue::assertPushed(RunMonitorCheck::class, 1);
});
