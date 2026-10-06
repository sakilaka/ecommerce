<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogDemoSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Galaxy A55 5G Official', 'category' => 'electronics', 'brand' => 'samsung', 'price' => 5299900, 'compare' => 5690000, 'sku' => 'EL-SAM-A55-001', 'badge' => 'Official', 'rating' => '4.8', 'reviews' => '1.4K', 'sold' => '620 sold', 'position' => '0% 0%', 'featured' => true],
            ['name' => 'Redmi Note 13 Pro', 'category' => 'electronics', 'brand' => 'xiaomi', 'price' => 2499900, 'compare' => 2750000, 'sku' => 'EL-XIA-N13-001', 'badge' => 'Best Seller', 'rating' => '4.8', 'reviews' => '1.9K', 'sold' => '860 sold', 'position' => '0% 0%', 'featured' => true],
            ['name' => 'Wireless Earbuds Pro', 'category' => 'electronics', 'brand' => 'xiaomi', 'price' => 249000, 'compare' => 310000, 'sku' => 'EL-XIA-EAR-001', 'badge' => 'Top Rated', 'rating' => '4.7', 'reviews' => '920', 'sold' => '1.1K sold', 'position' => '0% 0%', 'featured' => false],
            ['name' => 'Premium Eid Panjabi', 'category' => 'fashion', 'brand' => 'apex', 'price' => 175000, 'compare' => 225000, 'sku' => 'FA-APX-PAN-001', 'badge' => 'Best Seller', 'rating' => '4.9', 'reviews' => '2.1K', 'sold' => '1.2K sold', 'position' => '33.333% 0%', 'featured' => true],
            ['name' => 'Jamdani Inspired Saree', 'category' => 'fashion', 'brand' => 'apex', 'price' => 295000, 'compare' => 365000, 'sku' => 'FA-APX-SAR-001', 'badge' => 'Top Rated', 'rating' => '4.9', 'reviews' => '980', 'sold' => '740 sold', 'position' => '66.666% 0%', 'featured' => true],
            ['name' => 'Kids School Backpack', 'category' => 'kids', 'brand' => null, 'price' => 109000, 'compare' => 139000, 'sku' => 'KD-BAG-001', 'badge' => 'New', 'rating' => '4.9', 'reviews' => '410', 'sold' => '430 sold', 'position' => '100% 100%', 'featured' => false],
            ['name' => 'Hydrating Skincare Kit', 'category' => 'beauty', 'brand' => 'beautyhub', 'price' => 129000, 'compare' => 165000, 'sku' => 'BE-SKIN-001', 'badge' => 'Top Rated', 'rating' => '4.8', 'reviews' => '840', 'sold' => '690 sold', 'position' => '100% 0%', 'featured' => true],
            ['name' => 'Signature Perfume Gift Box', 'category' => 'beauty', 'brand' => 'beautyhub', 'price' => 189000, 'compare' => 240000, 'sku' => 'BE-GIFT-001', 'badge' => 'Gift Ready', 'rating' => '4.7', 'reviews' => '510', 'sold' => '380 sold', 'position' => '100% 0%', 'featured' => false],
            ['name' => 'Smart Blender Set', 'category' => 'home-living', 'brand' => 'walton', 'price' => 375000, 'compare' => 445000, 'sku' => 'HO-WAL-BLD-001', 'badge' => 'Free Delivery', 'rating' => '4.6', 'reviews' => '680', 'sold' => '410 sold', 'position' => '0% 100%', 'featured' => true],
            ['name' => 'Non-stick Cookware Combo', 'category' => 'home-living', 'brand' => 'singer', 'price' => 299000, 'compare' => 365000, 'sku' => 'HO-SIN-COK-001', 'badge' => 'Value Pick', 'rating' => '4.7', 'reviews' => '590', 'sold' => '300 sold', 'position' => '0% 100%', 'featured' => false],
            ['name' => 'Family Grocery Essentials', 'category' => 'groceries', 'brand' => null, 'price' => 119900, 'compare' => 142000, 'sku' => 'GR-FAM-001', 'badge' => 'Daily Need', 'rating' => '4.8', 'reviews' => '1.2K', 'sold' => '2K sold', 'position' => '66.666% 100%', 'featured' => true],
            ['name' => 'Student Laptop Stand', 'category' => 'electronics', 'brand' => 'samsung', 'price' => 115000, 'compare' => 150000, 'sku' => 'EL-LAP-STAND-001', 'badge' => 'New', 'rating' => '4.7', 'reviews' => '360', 'sold' => '350 sold', 'position' => '33.333% 100%', 'featured' => false],
        ];

        foreach ($products as $index => $item) {
            $category = Category::query()->where('slug', $item['category'])->firstOrFail();
            $brand = $item['brand'] ? Brand::query()->where('slug', $item['brand'])->first() : null;
            $slug = Str::slug($item['name']);

            $product = Product::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $category->id,
                    'brand_id' => $brand?->id,
                    'name' => $item['name'],
                    'sku' => $item['sku'],
                    'status' => ProductStatus::Active,
                    'short_description' => $item['name'].' for Bangladesh shoppers.',
                    'description' => 'Demo catalog product prepared for dynamic storefront browsing, filtering, and section feeds.',
                    'price' => $item['price'],
                    'compare_at_price' => $item['compare'],
                    'cost_price' => null,
                    'discount_percent' => (int) round((($item['compare'] - $item['price']) / $item['compare']) * 100),
                    'sale_starts_at' => now()->subDays(3),
                    'sale_ends_at' => now()->addDays(10),
                    'is_featured' => $item['featured'],
                    'is_refundable' => true,
                    'return_window_days' => 7,
                    'weight_kg' => 0.5,
                    'meta' => [
                        'badge' => $item['badge'],
                        'rating' => $item['rating'],
                        'reviews' => $item['reviews'],
                        'sold' => $item['sold'],
                        'image_position' => $item['position'],
                    ],
                    'published_at' => now()->subDays($index % 5),
                ],
            );

            ProductVariant::query()->updateOrCreate(
                ['sku' => $item['sku'].'-DEFAULT'],
                [
                    'product_id' => $product->id,
                    'name' => 'Default',
                    'options' => ['option' => 'Default'],
                    'price' => null,
                    'compare_at_price' => null,
                    'stock_quantity' => 25 + $index,
                    'reserved_quantity' => 0,
                    'low_stock_threshold' => 5,
                    'oversell_allowed' => true,
                    'is_active' => true,
                ],
            );

            ProductImage::query()->updateOrCreate(
                ['product_id' => $product->id, 'sort_order' => 0],
                [
                    'path' => '/images/category-collage.png',
                    'alt_text' => $item['name'],
                    'is_primary' => true,
                ],
            );
        }
    }
}
