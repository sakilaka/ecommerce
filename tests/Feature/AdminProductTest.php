<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_products_with_inventory_context(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Electronics']);
        $brand = Brand::factory()->create(['name' => 'Aurelia Tech', 'is_official' => true]);
        $product = Product::factory()->for($category)->for($brand)->create([
            'name' => 'Wireless Earbuds Pro',
            'sku' => 'AUR-EAR-001',
            'status' => ProductStatus::Active,
            'price' => 169900,
        ]);
        ProductVariant::factory()->for($product)->create([
            'stock_quantity' => 3,
            'reserved_quantity' => 1,
            'low_stock_threshold' => 5,
        ]);
        ProductVariant::factory()->for($product)->create([
            'stock_quantity' => 12,
            'reserved_quantity' => 2,
            'low_stock_threshold' => 5,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/products?q=earbuds&status=active');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Wireless Earbuds Pro')
            ->assertJsonPath('data.0.category.name', 'Electronics')
            ->assertJsonPath('data.0.brand.name', 'Aurelia Tech')
            ->assertJsonPath('data.0.variants_count', 2)
            ->assertJsonPath('data.0.stock_quantity', 15)
            ->assertJsonPath('data.0.reserved_quantity', 3)
            ->assertJsonPath('data.0.low_stock_variants', 1)
            ->assertJsonStructure(['data' => [['id', 'name', 'sku', 'status', 'price']], 'links', 'meta']);
    }

    public function test_admin_product_list_can_filter_drafts(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->create(['name' => 'Active Product', 'status' => ProductStatus::Active]);
        Product::factory()->draft()->create(['name' => 'Draft Product']);

        $response = $this->actingAs($admin)->getJson('/api/admin/products?status=draft');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Draft Product');
    }

    public function test_admin_can_load_product_form_options(): void
    {
        $admin = User::factory()->admin()->create();
        Category::factory()->create(['name' => 'Fashion', 'is_active' => true]);
        Category::factory()->create(['name' => 'Hidden', 'is_active' => false]);
        Brand::factory()->create(['name' => 'Verified Brand', 'is_active' => true, 'is_official' => true]);

        $response = $this->actingAs($admin)->getJson('/api/admin/product-options');

        $response->assertOk()
            ->assertJsonPath('categories.0.name', 'Fashion')
            ->assertJsonPath('brands.0.name', 'Verified Brand')
            ->assertJsonCount(3, 'statuses');
    }

    public function test_admin_can_create_product_with_variant_and_image(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create(['is_official' => true]);

        $response = $this->actingAs($admin)->postJson('/api/admin/products', [
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Premium Eid Panjabi',
            'sku' => 'AUR-PAN-001',
            'status' => 'active',
            'short_description' => 'Premium festive panjabi for Eid.',
            'description' => 'A breathable cotton panjabi with elevated finishing.',
            'seo_title' => 'Premium Eid Panjabi in Bangladesh',
            'seo_description' => 'Shop premium Eid panjabi with fast Bangladesh delivery.',
            'price' => 249900,
            'compare_at_price' => 299900,
            'cost_price' => 170000,
            'discount_percent' => 17,
            'is_featured' => true,
            'is_refundable' => true,
            'return_window_days' => 7,
            'weight_kg' => 0.55,
            'variants' => [[
                'name' => 'Maroon / M',
                'sku' => 'AUR-PAN-001-M',
                'price' => null,
                'compare_at_price' => null,
                'stock_quantity' => 15,
                'reserved_quantity' => 0,
                'low_stock_threshold' => 4,
                'oversell_allowed' => true,
                'is_active' => true,
            ]],
            'images' => [[
                'path' => '/images/products/eid-panjabi.jpg',
                'alt_text' => 'Premium Eid Panjabi',
                'is_primary' => true,
            ]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Premium Eid Panjabi')
            ->assertJsonPath('data.slug', 'premium-eid-panjabi')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.seo_title', 'Premium Eid Panjabi in Bangladesh')
            ->assertJsonPath('data.seo_description', 'Shop premium Eid panjabi with fast Bangladesh delivery.')
            ->assertJsonPath('data.variants.0.sku', 'AUR-PAN-001-M')
            ->assertJsonPath('data.images.0.path', '/images/products/eid-panjabi.jpg');

        $this->assertDatabaseHas('products', ['sku' => 'AUR-PAN-001', 'status' => 'active', 'seo_title' => 'Premium Eid Panjabi in Bangladesh']);
        $this->assertDatabaseHas('product_variants', ['sku' => 'AUR-PAN-001-M', 'stock_quantity' => 15]);
        $this->assertDatabaseHas('product_images', ['path' => '/images/products/eid-panjabi.jpg', 'is_primary' => true]);
    }

    public function test_admin_can_update_product_and_replace_variant_set(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create(['name' => 'Old Product', 'status' => ProductStatus::Draft]);
        $keptVariant = ProductVariant::factory()->for($product)->create(['sku' => 'KEEP-001', 'stock_quantity' => 3]);
        $removedVariant = ProductVariant::factory()->for($product)->create(['sku' => 'REMOVE-001']);
        ProductImage::factory()->primary()->for($product)->create(['path' => '/old.jpg']);

        $response = $this->actingAs($admin)->patchJson('/api/admin/products/'.$product->id, [
            'category_id' => $category->id,
            'brand_id' => null,
            'name' => 'Updated Product',
            'slug' => 'updated-product',
            'sku' => 'UPDATED-001',
            'status' => 'draft',
            'short_description' => null,
            'description' => null,
            'seo_title' => 'Updated product SEO title',
            'seo_description' => 'Updated product SEO description for search.',
            'price' => 99900,
            'compare_at_price' => 129900,
            'cost_price' => null,
            'discount_percent' => null,
            'is_featured' => false,
            'is_refundable' => false,
            'return_window_days' => 0,
            'weight_kg' => null,
            'variants' => [[
                'id' => $keptVariant->id,
                'name' => 'Default Updated',
                'sku' => 'KEEP-001',
                'price' => null,
                'compare_at_price' => null,
                'stock_quantity' => 25,
                'reserved_quantity' => 1,
                'low_stock_threshold' => 5,
                'oversell_allowed' => true,
                'is_active' => true,
            ]],
            'images' => [[
                'path' => '/new.jpg',
                'alt_text' => 'Updated Product',
                'is_primary' => true,
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Product')
            ->assertJsonPath('data.seo_title', 'Updated product SEO title')
            ->assertJsonPath('data.seo_description', 'Updated product SEO description for search.')
            ->assertJsonPath('data.variants.0.stock_quantity', 25)
            ->assertJsonPath('data.images.0.path', '/new.jpg');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated Product', 'is_refundable' => false, 'seo_title' => 'Updated product SEO title']);
        $this->assertDatabaseHas('product_variants', ['id' => $keptVariant->id, 'stock_quantity' => 25]);
        $this->assertDatabaseMissing('product_variants', ['id' => $removedVariant->id]);
        $this->assertDatabaseMissing('product_images', ['path' => '/old.jpg']);
    }

    public function test_product_create_requires_valid_catalog_data(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/products', [
            'name' => '',
            'status' => 'invalid',
            'price' => 0,
            'variants' => [],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'name', 'status', 'price', 'variants']);
    }

    public function test_customer_cannot_list_admin_products(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($customer)->getJson('/api/admin/products');

        $response->assertForbidden();
    }
}
