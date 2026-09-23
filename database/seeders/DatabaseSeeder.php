<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'i2007f2007@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
            ]
        );

        $this->call([
            TeamSeeder::class,
            ProjectSeeder::class,
            MonitorSeeder::class,
        ]);
    }
}
