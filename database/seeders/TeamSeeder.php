<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owner = User::query()->where('email', 'i2007f2007@gmail.com')->first()
            ?? User::factory()->create([
                'name' => 'Super Admin',
                'email' => 'i2007f2007@gmail.com',
                'password' => 'password',
            ]);

        $admin = User::factory()->create([
            'name' => 'Team Admin',
            'email' => 'admin@pulse.test',
        ]);

        $visitor = User::factory()->create([
            'name' => 'Team Visitor',
            'email' => 'visitor@pulse.test',
        ]);

        $team = Team::factory()->ownedBy($owner)->create([
            'name' => 'Pulse Demo',
        ]);

        $team->addMember($admin, TeamRole::Admin);
        $team->addMember($visitor, TeamRole::Visitor);

        Team::factory()
            ->count(2)
            ->ownedBy($owner)
            ->create();
    }
}
