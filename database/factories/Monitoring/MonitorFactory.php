<?php

namespace Database\Factories\Monitoring;

use App\Models\Monitoring\Monitor;
use App\Models\Monitoring\MonitorAssertion;
use App\Models\Monitoring\MonitorHeader;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Monitor>
 */
class MonitorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $intervalSeconds = fake()->randomElement([30, 60, 120, 300, 600]);

        return [
            'name' => fake()->unique()->words(2, true).' monitor',
            'description' => fake()->optional()->sentence(),
            'enabled' => true,
            'project_id' => Project::factory(),
            'url' => fake()->domainName().'/health',
            'type' => fake()->randomElement(Monitor::TYPE_OPTIONS),
            'method' => 'get',
            'timeout_ms' => min(5000, ($intervalSeconds * 1000) - 100),
            'interval_seconds' => $intervalSeconds,
            'expected_status' => '200',
            'last_checked_at' => null,
            'next_check_at' => now(),
        ];
    }

    public function forProject(Project $project): static
    {
        return $this->state(fn () => [
            'project_id' => $project->id,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn () => [
            'enabled' => false,
        ]);
    }

    public function withHeaders(int $count = 2): static
    {
        return $this->afterCreating(function (Monitor $monitor) use ($count) {
            MonitorHeader::factory()
                ->count($count)
                ->for($monitor)
                ->create();
        });
    }

    public function withAssertions(int $count = 2): static
    {
        return $this->afterCreating(function (Monitor $monitor) use ($count) {
            MonitorAssertion::factory()
                ->count($count)
                ->for($monitor)
                ->create();
        });
    }

    public function fullyConfigured(int $headers = 2, int $assertions = 2): static
    {
        return $this->withHeaders($headers)->withAssertions($assertions);
    }
}
