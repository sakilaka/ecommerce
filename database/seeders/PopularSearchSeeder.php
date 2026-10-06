<?php

namespace Database\Seeders;

use App\Models\PopularSearch;
use Illuminate\Database\Seeder;

class PopularSearchSeeder extends Seeder
{
    public function run(): void
    {
        $searches = [
            ['Smartphones in Bangladesh', 'বাংলাদেশে স্মার্টফোন', 'smartphones bangladesh', '/products?q=smartphones%20bangladesh'],
            ['Men’s Shoes', 'পুরুষদের জুতা', 'men shoes', '/products?q=men%20shoes'],
            ['Laptops', 'ল্যাপটপ', 'laptops', '/products?q=laptops'],
            ['Smart Watches', 'স্মার্ট ওয়াচ', 'smart watches', '/products?topic=smart-watches'],
            ['Kitchen Appliances', 'কিচেন অ্যাপ্লায়েন্স', 'kitchen appliances', '/products?q=kitchen%20appliances'],
            ['Skin Care', 'স্কিন কেয়ার', 'skin care', '/products?q=skin%20care'],
            ['Eid Panjabi', 'ঈদ পাঞ্জাবি', 'eid panjabi', '/products?q=eid%20panjabi'],
            ['Women’s Saree', 'মেয়েদের শাড়ি', 'women saree', '/products?q=women%20saree'],
        ];

        foreach ($searches as $index => [$labelEn, $labelBn, $keyword, $href]) {
            PopularSearch::query()->updateOrCreate(
                ['keyword' => $keyword],
                [
                    'label_en' => $labelEn,
                    'label_bn' => $labelBn,
                    'href' => $href,
                    'sort_order' => $index + 1,
                    'click_count' => 0,
                    'is_active' => true,
                ],
            );
        }
    }
}
