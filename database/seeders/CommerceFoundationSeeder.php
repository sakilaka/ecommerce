<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\DeliveryZone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CommerceFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Electronics',
            'Fashion',
            'Beauty',
            'Home & Living',
            'Groceries',
            'Sports',
            'Kids',
            'Automotive',
            'Offers',
        ];

        foreach ($categories as $index => $name) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ],
            );
        }

        $brands = [
            ['name' => 'Samsung', 'is_official' => true],
            ['name' => 'Xiaomi', 'is_official' => true],
            ['name' => 'Walton', 'is_official' => true],
            ['name' => 'Singer', 'is_official' => true],
            ['name' => 'Apex', 'is_official' => true],
            ['name' => 'BeautyHub', 'is_official' => false],
        ];

        foreach ($brands as $brand) {
            Brand::query()->updateOrCreate(
                ['slug' => Str::slug($brand['name'])],
                [
                    'name' => $brand['name'],
                    'is_official' => $brand['is_official'],
                    'is_active' => true,
                ],
            );
        }

        $zones = [
            ['district' => 'Dhaka', 'area' => 'Inside Dhaka', 'delivery_charge' => 8000, 'estimated_min_days' => 1, 'estimated_max_days' => 2],
            ['district' => 'Dhaka', 'area' => 'Outside Dhaka', 'delivery_charge' => 12000, 'estimated_min_days' => 2, 'estimated_max_days' => 4],
            ['district' => 'Chattogram', 'area' => null, 'delivery_charge' => 13000, 'estimated_min_days' => 2, 'estimated_max_days' => 4],
            ['district' => 'Sylhet', 'area' => null, 'delivery_charge' => 13000, 'estimated_min_days' => 2, 'estimated_max_days' => 5],
            ['district' => 'Rajshahi', 'area' => null, 'delivery_charge' => 14000, 'estimated_min_days' => 3, 'estimated_max_days' => 5],
            ['district' => 'Khulna', 'area' => null, 'delivery_charge' => 14000, 'estimated_min_days' => 3, 'estimated_max_days' => 5],
        ];

        foreach ($zones as $zone) {
            DeliveryZone::query()->updateOrCreate(
                ['district' => $zone['district'], 'area' => $zone['area']],
                [
                    'delivery_charge' => $zone['delivery_charge'],
                    'cod_charge' => 0,
                    'estimated_min_days' => $zone['estimated_min_days'],
                    'estimated_max_days' => $zone['estimated_max_days'],
                    'cod_available' => true,
                    'is_active' => true,
                ],
            );
        }
    }
}
