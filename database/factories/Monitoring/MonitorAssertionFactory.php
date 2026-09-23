<?php

namespace Database\Factories\Monitoring;

use App\Models\Monitoring\Monitor;
use App\Models\Monitoring\MonitorAssertion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitorAssertion>
 */
class MonitorAssertionFactory extends Factory
{
    protected $model = MonitorAssertion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(MonitorAssertion::TYPE_OPTIONS);

        return match ($type) {
            'status' => [
                'monitor_id' => Monitor::factory(),
                'type' => 'status',
                'field' => null,
                'operator' => 'equal',
                'expected_value' => '200',
            ],
            'json' => [
                'monitor_id' => Monitor::factory(),
                'type' => 'json',
                'field' => fake()->randomElement(['status', 'ok', 'data.healthy']),
                'operator' => fake()->randomElement(['equal', 'not_equal', 'contains']),
                'expected_value' => fake()->randomElement(['ok', 'true', 'healthy']),
            ],
            'contains' => [
                'monitor_id' => Monitor::factory(),
                'type' => 'contains',
                'field' => null,
                'operator' => 'contains',
                'expected_value' => fake()->randomElement(['healthy', 'ok', 'uptime']),
            ],
            'latency' => [
                'monitor_id' => Monitor::factory(),
                'type' => 'latency',
                'field' => null,
                'operator' => fake()->randomElement(['lt', 'gt']),
                'expected_value' => (string) fake()->randomElement([200, 500, 1000, 2000]),
            ],
            default => [
                'monitor_id' => Monitor::factory(),
                'type' => 'status',
                'field' => null,
                'operator' => 'equal',
                'expected_value' => '200',
            ],
        };
    }

    public function status(int $expected = 200): static
    {
        return $this->state(fn () => [
            'type' => 'status',
            'field' => null,
            'operator' => 'equal',
            'expected_value' => (string) $expected,
        ]);
    }

    public function latencyUnder(int $ms = 500): static
    {
        return $this->state(fn () => [
            'type' => 'latency',
            'field' => null,
            'operator' => 'lt',
            'expected_value' => (string) $ms,
        ]);
    }
}
