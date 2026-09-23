<?php

namespace Database\Seeders;

use App\Models\Monitoring\Monitor;
use App\Models\Monitoring\MonitorAssertion;
use App\Models\Monitoring\MonitorHeader;
use App\Models\Project;
use Illuminate\Database\Seeder;

class MonitorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::query()->get();

        if ($projects->isEmpty()) {
            $this->call(ProjectSeeder::class);
            $projects = Project::query()->get();
        }

        foreach ($projects as $project) {
            Monitor::factory()
                ->count(2)
                ->forProject($project)
                ->fullyConfigured()
                ->create();
        }

        $production = Project::query()->where('name', 'Production API')->first();

        if ($production === null) {
            return;
        }

        $monitor = Monitor::factory()->forProject($production)->create([
            'name' => 'API health',
            'description' => 'Primary health endpoint for the demo stack.',
            'url' => 'httpbin.org/status/200',
            'type' => 'https',
            'method' => 'get',
            'interval_seconds' => 60,
            'timeout_ms' => 3000,
            'expected_status' => '200',
            'enabled' => true,
            'next_check_at' => now(),
        ]);

        MonitorHeader::factory()->for($monitor)->create([
            'key' => 'Accept',
            'value' => 'application/json',
        ]);

        MonitorHeader::factory()->for($monitor)->create([
            'key' => 'X-Pulse-Probe',
            'value' => 'demo',
        ]);

        MonitorAssertion::factory()->for($monitor)->status(200)->create();
        MonitorAssertion::factory()->for($monitor)->latencyUnder(1500)->create();
    }
}
