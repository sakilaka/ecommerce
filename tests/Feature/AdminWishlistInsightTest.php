<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWishlistInsightTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_ranked_wishlist_insights(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Electronics']);
        $brand = Brand::factory()->create(['name' => 'Samsung']);
        $popular = Product::factory()->for($category)->for($brand)->create(['name' => 'Galaxy A55 Official', 'sku' => 'GAL-A55']);
        $lessPopular = Product::factory()->create(['name' => 'Cotton Panjabi']);

        WishlistItem::factory()->for(User::factory()->create())->for($popular)->create(['created_at' => now()->subHour()]);
        WishlistItem::factory()->for(User::factory()->create())->for($popular)->create(['created_at' => now()]);
        WishlistItem::factory()->for(User::factory()->create())->for($lessPopular)->create(['created_at' => now()->subDay()]);

        $response = $this->actingAs($admin)->getJson('/api/admin/wishlist-insights?q=galaxy');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $popular->id)
            ->assertJsonPath('data.0.wishlist_count', 2)
            ->assertJsonPath('data.0.unique_customers', 2)
            ->assertJsonPath('data.0.category.name', 'Electronics')
            ->assertJsonPath('data.0.brand.name', 'Samsung')
            ->assertJsonStructure(['data' => [['id', 'name', 'sku', 'wishlist_count', 'unique_customers', 'latest_wishlisted_at', 'category', 'brand']], 'links', 'meta']);
    }

    public function test_admin_wishlist_insights_exclude_products_without_wishlist_signal(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->create(['name' => 'Unsaved Product']);

        $response = $this->actingAs($admin)->getJson('/api/admin/wishlist-insights');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_customer_cannot_view_admin_wishlist_insights(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->getJson('/api/admin/wishlist-insights')->assertForbidden();
    }
}
