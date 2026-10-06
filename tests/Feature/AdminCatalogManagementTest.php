<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_filter_categories(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Electronics', 'is_active' => true, 'sort_order' => 1]);
        Category::factory()->create(['name' => 'Hidden Category', 'is_active' => false]);
        Product::factory()->for($category)->create();

        $response = $this->actingAs($admin)->getJson('/api/admin/categories?q=elect&status=active');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Electronics')
            ->assertJsonPath('data.0.products_count', 1)
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'is_active']], 'links', 'meta']);
    }

    public function test_admin_can_create_and_update_category(): void
    {
        $admin = User::factory()->admin()->create();
        $parent = Category::factory()->create(['name' => 'Fashion']);

        $create = $this->actingAs($admin)->postJson('/api/admin/categories', [
            'parent_id' => $parent->id,
            'name' => 'Men Fashion',
            'slug' => null,
            'image_path' => '/images/categories/men.jpg',
            'description' => 'Panjabi, shirts, shoes and watches.',
            'seo_title' => 'Men Fashion Bangladesh',
            'seo_description' => 'Shop panjabi, shirts, shoes and watches for men.',
            'sort_order' => 5,
            'is_active' => true,
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.name', 'Men Fashion')
            ->assertJsonPath('data.slug', 'men-fashion')
            ->assertJsonPath('data.seo_title', 'Men Fashion Bangladesh')
            ->assertJsonPath('data.seo_description', 'Shop panjabi, shirts, shoes and watches for men.')
            ->assertJsonPath('data.parent.name', 'Fashion');

        $categoryId = $create->json('data.id');

        $update = $this->actingAs($admin)->patchJson('/api/admin/categories/'.$categoryId, [
            'parent_id' => null,
            'name' => 'Menswear',
            'slug' => 'menswear',
            'image_path' => '/images/categories/menswear.jpg',
            'description' => 'Updated department.',
            'seo_title' => 'Menswear Online Bangladesh',
            'seo_description' => 'Updated menswear category SEO description.',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $update->assertOk()
            ->assertJsonPath('data.name', 'Menswear')
            ->assertJsonPath('data.slug', 'menswear')
            ->assertJsonPath('data.seo_title', 'Menswear Online Bangladesh')
            ->assertJsonPath('data.seo_description', 'Updated menswear category SEO description.')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('categories', ['id' => $categoryId, 'name' => 'Menswear', 'is_active' => false, 'seo_title' => 'Menswear Online Bangladesh']);
    }

    public function test_admin_can_list_and_filter_brands(): void
    {
        $admin = User::factory()->admin()->create();
        $brand = Brand::factory()->create(['name' => 'Aurelia Official', 'is_active' => true, 'is_official' => true]);
        Brand::factory()->create(['name' => 'Regular Brand', 'is_active' => true, 'is_official' => false]);
        Product::factory()->for($brand)->create();

        $response = $this->actingAs($admin)->getJson('/api/admin/brands?official=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Aurelia Official')
            ->assertJsonPath('data.0.products_count', 1)
            ->assertJsonPath('data.0.is_official', true);
    }

    public function test_admin_can_create_and_update_brand(): void
    {
        $admin = User::factory()->admin()->create();

        $create = $this->actingAs($admin)->postJson('/api/admin/brands', [
            'name' => 'Nova Tech',
            'slug' => null,
            'logo_path' => '/images/brands/nova.svg',
            'description' => 'Verified electronics partner.',
            'seo_title' => 'Nova Tech Official Store',
            'seo_description' => 'Buy verified Nova Tech electronics in Bangladesh.',
            'is_official' => true,
            'is_active' => true,
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.name', 'Nova Tech')
            ->assertJsonPath('data.slug', 'nova-tech')
            ->assertJsonPath('data.seo_title', 'Nova Tech Official Store')
            ->assertJsonPath('data.seo_description', 'Buy verified Nova Tech electronics in Bangladesh.')
            ->assertJsonPath('data.is_official', true);

        $brandId = $create->json('data.id');

        $update = $this->actingAs($admin)->patchJson('/api/admin/brands/'.$brandId, [
            'name' => 'Nova Electronics',
            'slug' => 'nova-electronics',
            'logo_path' => '/images/brands/nova-new.svg',
            'description' => 'Updated brand partner.',
            'seo_title' => 'Nova Electronics Bangladesh',
            'seo_description' => 'Updated Nova Electronics search summary.',
            'is_official' => false,
            'is_active' => false,
        ]);

        $update->assertOk()
            ->assertJsonPath('data.name', 'Nova Electronics')
            ->assertJsonPath('data.seo_title', 'Nova Electronics Bangladesh')
            ->assertJsonPath('data.seo_description', 'Updated Nova Electronics search summary.')
            ->assertJsonPath('data.is_official', false)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('brands', ['id' => $brandId, 'name' => 'Nova Electronics', 'is_active' => false, 'seo_title' => 'Nova Electronics Bangladesh']);
    }

    public function test_catalog_management_requires_admin_access(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)->getJson('/api/admin/categories')->assertForbidden();
        $this->actingAs($customer)->getJson('/api/admin/brands')->assertForbidden();
    }
}
