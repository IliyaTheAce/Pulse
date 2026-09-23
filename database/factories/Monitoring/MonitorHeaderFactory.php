<?php

namespace Database\Factories\Monitoring;

use App\Models\Monitoring\Monitor;
use App\Models\Monitoring\MonitorHeader;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitorHeader>
 */
class MonitorHeaderFactory extends Factory
{
    protected $model = MonitorHeader::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::factory(),
            'key' => fake()->randomElement([
                'Accept',
                'Authorization',
                'X-Request-Id',
                'X-Api-Key',
                'User-Agent',
            ]),
            'value' => fake()->sha256(),
        ];
    }
}
