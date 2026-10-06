<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'phone' => '01700000000',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
        );

        $this->call([
            AdminUserSeeder::class,
            CommerceFoundationSeeder::class,
            CouponSeeder::class,
            PopularSearchSeeder::class,
            HomepageSectionSeeder::class,
            CatalogDemoSeeder::class,
        ]);
    }
}
