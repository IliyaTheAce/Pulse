<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Team;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = Team::query()->get();

        if ($teams->isEmpty()) {
            $this->call(TeamSeeder::class);
            $teams = Team::query()->get();
        }

        foreach ($teams as $team) {
            Project::factory()
                ->count(2)
                ->forTeam($team)
                ->create();
        }

        $demoTeam = Team::query()->where('name', 'Pulse Demo')->first();

        if ($demoTeam !== null) {
            Project::factory()->forTeam($demoTeam)->create([
                'name' => 'Production API',
                'description' => 'Public endpoints that must stay healthy.',
            ]);

            Project::factory()->forTeam($demoTeam)->create([
                'name' => 'Staging API',
                'description' => 'Pre-production checks before release.',
            ]);
        }
    }
}
