<?php

use App\Models\Monitoring\Monitor;
use App\Models\Monitoring\MonitorResult;
use App\Models\Project;
use App\Models\Team;

it('does not insert two rows for the same execution_id', function () {

    $team = Team::query()->create([
        "name" => "test team"
    ]);

    $project = Project::query()->create([
        "name" => "third test project",
        "description" => "lorep ipsum",
        "team_id" => $team->id
    ]);

    $monitor = Monitor::query()->create([
        "name" => "test assertions",
        "description" => "test schedule and manual run",
        "type" => "http",
        "method" => "get",
        "url" => "31.7.78.93:48080/up",
        "interval_seconds" => "600",
        "timeout_ms" => "2000",
        "expected_status" => "200",
        "project_id" => $project->id,
    ]);

    $payload = [
        'monitor_id' => $monitor->id,
        'team_id' => $team->id,
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

    expect(MonitorResult::query()->where('execution_id', "=", 'same-id')->count())->toBe(1);
});
