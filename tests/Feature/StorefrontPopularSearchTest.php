<?php

namespace Tests\Feature;

use App\Models\PopularSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontPopularSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_returns_active_popular_searches_in_merchandising_order(): void
    {
        PopularSearch::factory()->create([
            'label_en' => 'Laptops',
            'label_bn' => 'ল্যাপটপ',
            'keyword' => 'laptops',
            'href' => '/products?q=laptops',
            'sort_order' => 2,
            'click_count' => 80,
            'is_active' => true,
        ]);
        PopularSearch::factory()->create([
            'label_en' => 'Smartphones in Bangladesh',
            'keyword' => 'smartphones bangladesh',
            'href' => '/products?q=smartphones%20bangladesh',
            'sort_order' => 1,
            'click_count' => 20,
            'is_active' => true,
        ]);
        PopularSearch::factory()->inactive()->create([
            'label_en' => 'Hidden Search',
            'keyword' => 'hidden',
            'href' => '/products?q=hidden',
            'sort_order' => 0,
        ]);

        $response = $this->getJson('/api/storefront/popular-searches?limit=10');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.label_en', 'Smartphones in Bangladesh')
            ->assertJsonPath('data.1.label_bn', 'ল্যাপটপ')
            ->assertJsonMissingPath('data.0.click_count');
    }
}
