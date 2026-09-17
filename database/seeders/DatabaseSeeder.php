<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::query()->create([
            'name' => 'Super Admin',
            'email' => 'i2007f2007@gmail.com',
            'password' => bcrypt('password'),
        ]);

//        Role::create([
//            'name' => 'Admin',
//            'team_id' => 1,
//        ]);

//        $user->assignRole('Admin');
    }
}
