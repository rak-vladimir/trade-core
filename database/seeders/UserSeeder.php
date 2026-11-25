<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@trade-core.local'],
            [
                'name'     => 'Administrator',
                'password' => 'password',
                'role'     => UserRole::Administrator,
            ],
        );

        User::updateOrCreate(
            ['email' => 'manager@trade-core.local'],
            [
                'name'     => 'Manager',
                'password' => 'password',
                'role'     => UserRole::Manager,
            ],
        );
    }
}
