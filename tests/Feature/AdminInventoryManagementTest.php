<?php

namespace Tests\Feature;

use App\Enums\InventoryAdjustmentType;
use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_filter_inventory_items(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Electronics']);
        $brand = Brand::factory()->create(['name' => 'Nova Official', 'is_official' => true]);
        $product = Product::factory()->for($category)->for($brand)->create(['name' => 'Nova Earbuds', 'sku' => 'PROD-NOVA']);
        $variant = ProductVariant::factory()->for($product)->create([
            'name' => 'Black',
            'sku' => 'NOVA-EARBUDS-BLK',
            'stock_quantity' => 3,
            'reserved_quantity' => 1,
            'low_stock_threshold' => 5,
        ]);
        ProductVariant::factory()->create(['sku' => 'HEALTHY-STOCK', 'stock_quantity' => 40, 'low_stock_threshold' => 5]);

        $response = $this->actingAs($admin)->getJson('/api/admin/inventory?q=earbuds&stock_status=low&active=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $variant->id)
            ->assertJsonPath('data.0.sku', 'NOVA-EARBUDS-BLK')
            ->assertJsonPath('data.0.available_quantity', 2)
            ->assertJsonPath('data.0.is_low_stock', true)
            ->assertJsonPath('data.0.product.category.name', 'Electronics')
            ->assertJsonPath('data.0.product.brand.is_official', true)
            ->assertJsonStructure(['data' => [['id', 'sku', 'stock_quantity', 'reserved_quantity', 'available_quantity', 'product']], 'links', 'meta']);
    }

    public function test_admin_can_adjust_variant_stock_with_audit_entry(): void
    {
        $admin = User::factory()->admin()->create();
        $variant = ProductVariant::factory()->create(['stock_quantity' => 8]);

        $response = $this->actingAs($admin)->postJson('/api/admin/inventory/'.$variant->id.'/adjustments', [
            'type' => InventoryAdjustmentType::Manual->value,
            'quantity_delta' => 7,
            'note' => 'Supplier delivery counted in warehouse.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $variant->id)
            ->assertJsonPath('data.stock_quantity', 15)
            ->assertJsonPath('data.adjustments_count', 1);

        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock_quantity' => 15]);
        $this->assertDatabaseHas('inventory_adjustments', [
            'product_variant_id' => $variant->id,
            'user_id' => $admin->id,
            'type' => InventoryAdjustmentType::Manual->value,
            'quantity_delta' => 7,
            'stock_after' => 15,
            'reference_type' => 'admin_adjustment',
        ]);
    }

    public function test_stock_adjustment_cannot_reduce_stock_below_zero(): void
    {
        $admin = User::factory()->admin()->create();
        $variant = ProductVariant::factory()->create(['stock_quantity' => 4]);

        $response = $this->actingAs($admin)->postJson('/api/admin/inventory/'.$variant->id.'/adjustments', [
            'type' => InventoryAdjustmentType::Correction->value,
            'quantity_delta' => -5,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity_delta']);

        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock_quantity' => 4]);
        $this->assertDatabaseMissing('inventory_adjustments', ['product_variant_id' => $variant->id, 'quantity_delta' => -5]);
    }

    public function test_customer_cannot_manage_inventory(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $variant = ProductVariant::factory()->create();

        $this->actingAs($customer)->getJson('/api/admin/inventory')->assertForbidden();
        $this->actingAs($customer)->postJson('/api/admin/inventory/'.$variant->id.'/adjustments', [
            'type' => InventoryAdjustmentType::Manual->value,
            'quantity_delta' => 2,
        ])->assertForbidden();
    }
}
