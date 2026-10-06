<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PopularSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPopularSearchManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_filter_create_and_update_popular_searches(): void
    {
        $admin = User::factory()->admin()->create();
        PopularSearch::factory()->create([
            'label_en' => 'Smart Watches',
            'keyword' => 'smart watches',
            'href' => '/products?topic=smart-watches',
            'sort_order' => 2,
            'click_count' => 12,
            'is_active' => true,
        ]);
        PopularSearch::factory()->inactive()->create([
            'label_en' => 'Hidden Term',
            'keyword' => 'hidden',
        ]);

        $list = $this->actingAs($admin)->getJson('/api/admin/popular-searches?q=smart&status=active');

        $list->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label_en', 'Smart Watches')
            ->assertJsonPath('data.0.href', '/products?topic=smart-watches');

        $create = $this->actingAs($admin)->postJson('/api/admin/popular-searches', [
            'label_en' => 'Skin Care',
            'label_bn' => 'স্কিন কেয়ার',
            'keyword' => 'skin care',
            'href' => '/products?q=skin%20care',
            'sort_order' => 1,
            'click_count' => 5,
            'is_active' => true,
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.label_en', 'Skin Care')
            ->assertJsonPath('data.label_bn', 'স্কিন কেয়ার')
            ->assertJsonPath('data.click_count', 5);

        $searchId = $create->json('data.id');

        $update = $this->actingAs($admin)->patchJson('/api/admin/popular-searches/'.$searchId, [
            'label_en' => 'Beauty Skin Care',
            'label_bn' => 'বিউটি স্কিন কেয়ার',
            'keyword' => 'beauty skin care',
            'href' => '/products?q=beauty%20skin%20care',
            'sort_order' => 3,
            'click_count' => 8,
            'is_active' => false,
        ]);

        $update->assertOk()
            ->assertJsonPath('data.label_en', 'Beauty Skin Care')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('popular_searches', [
            'id' => $searchId,
            'label_en' => 'Beauty Skin Care',
            'href' => '/products?q=beauty%20skin%20care',
            'is_active' => false,
        ]);
    }

    public function test_popular_search_requires_product_target_url(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/popular-searches', [
            'label_en' => 'External Search',
            'keyword' => 'external',
            'href' => 'https://example.com',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['href']);
    }

    public function test_customer_cannot_manage_popular_searches(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)->getJson('/api/admin/popular-searches')->assertForbidden();
    }
}
