<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@aurelia.test'],
            [
                'name' => 'Aurelia Admin',
                'phone' => '01900000000',
                'password' => Hash::make('Admin@12345'),
                'role' => UserRole::Admin,
                'is_active' => true,
                'permissions' => ['*'],
            ],
        );
    }
}
