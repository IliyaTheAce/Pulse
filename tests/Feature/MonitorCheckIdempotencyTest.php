<?php

use App\Models\Monitoring\MonitorResult;

it('does not insert two rows for the same execution_id', function () {
    $payload = [
        'monitor_id' => 1,
        'team_id' => 1,
        'checked_at' => now(),
        'status' => 'success',
        'http_status' => 200,
        'duration_ms' => 10,
        'error_type' => null,
        'assertion_failures' => null,
        'response_bytes' => 0,
    ];

    MonitorResult::firstOrCreate(['execution_id' => 'same-id'], $payload);
    MonitorResult::firstOrCreate(['execution_id' => 'same-id'], $payload);

    expect(MonitorResult::query()->where('execution_id', 'same-id')->count())->toBe(1);
});