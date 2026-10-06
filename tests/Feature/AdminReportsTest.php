<?php

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Coupon;
use App\Models\HomepageSection;
use App\Models\NewsletterLead;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PopularSearch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_sales_and_operations_report(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create(['role' => UserRole::Customer, 'created_at' => now()->subDays(2)]);
        $coupon = Coupon::factory()->create(['code' => 'EID20', 'type' => DiscountType::Percent, 'value' => 20]);
        $product = Product::factory()->create(['name' => 'Premium Panjabi', 'status' => ProductStatus::Active]);
        $variant = ProductVariant::factory()->for($product)->create(['name' => 'Black', 'sku' => 'PANJABI-BLK', 'stock_quantity' => 2, 'low_stock_threshold' => 5]);

        $paidOrder = Order::factory()->for($customer)->for($coupon)->create([
            'status' => OrderStatus::Delivered,
            'payment_status' => PaymentStatus::Paid,
            'grand_total' => 200000,
            'discount_total' => 20000,
            'created_at' => now()->subDays(1),
        ]);
        OrderItem::factory()->for($paidOrder)->for($product)->create([
            'product_variant_id' => $variant->id,
            'product_name' => 'Premium Panjabi',
            'quantity' => 2,
            'line_total' => 180000,
        ]);

        Order::factory()->for($customer)->create([
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'grand_total' => 90000,
            'coupon_id' => null,
            'created_at' => now()->subDays(3),
        ]);
        Order::factory()->for($customer)->create([
            'payment_status' => PaymentStatus::Paid,
            'grand_total' => 100000,
            'coupon_id' => null,
            'created_at' => now()->subDays(40),
        ]);
        NewsletterLead::factory()->create(['source' => 'homepage', 'interest' => 'eid-offers', 'created_at' => now()->subDays(2), 'is_active' => true]);
        NewsletterLead::factory()->create(['source' => 'footer', 'interest' => 'electronics', 'created_at' => now()->subDays(5), 'is_active' => true]);
        NewsletterLead::factory()->inactive()->create(['source' => 'homepage', 'interest' => null, 'created_at' => now()->subDays(3)]);
        PopularSearch::factory()->create(['label_en' => 'Smartphones in Bangladesh', 'keyword' => 'smartphones', 'click_count' => 85, 'is_active' => true]);
        PopularSearch::factory()->create(['label_en' => 'Men Shoes', 'keyword' => 'men shoes', 'click_count' => 120, 'is_active' => true]);
        PopularSearch::factory()->inactive()->create(['label_en' => 'Hidden Search', 'click_count' => 999]);
        HomepageSection::factory()->create(['key' => 'eid-campaign', 'title' => 'Eid Campaign', 'sort_order' => 5, 'is_active' => true]);
        HomepageSection::factory()->inactive()->create(['key' => 'hidden-campaign']);
        HomepageSection::factory()->expired()->create(['key' => 'expired-campaign']);

        $response = $this->actingAs($admin)->getJson('/api/admin/reports?period=30');

        $response->assertOk()
            ->assertJsonPath('data.period.days', 30)
            ->assertJsonPath('data.summary.revenue', 200000)
            ->assertJsonPath('data.summary.orders', 2)
            ->assertJsonPath('data.summary.average_order_value', 100000)
            ->assertJsonPath('data.summary.delivered_orders', 1)
            ->assertJsonPath('data.summary.new_customers', 1)
            ->assertJsonPath('data.summary.low_stock_variants', 1)
            ->assertJsonPath('data.top_products.0.product_name', 'Premium Panjabi')
            ->assertJsonPath('data.top_products.0.units_sold', 2)
            ->assertJsonPath('data.stock_risk.0.sku', 'PANJABI-BLK')
            ->assertJsonPath('data.coupon_performance.0.code', 'EID20')
            ->assertJsonPath('data.marketing_insights.summary.running_coupons', 1)
            ->assertJsonPath('data.marketing_insights.summary.active_coupons', 1)
            ->assertJsonPath('data.marketing_insights.summary.coupon_orders', 1)
            ->assertJsonPath('data.marketing_insights.summary.coupon_order_rate_percent', 50)
            ->assertJsonPath('data.marketing_insights.summary.coupon_discount_total', 20000)
            ->assertJsonPath('data.marketing_insights.summary.new_leads', 3)
            ->assertJsonPath('data.marketing_insights.summary.active_leads', 2)
            ->assertJsonPath('data.marketing_insights.summary.active_popular_searches', 2)
            ->assertJsonPath('data.marketing_insights.summary.active_campaign_sections', 1)
            ->assertJsonPath('data.marketing_insights.lead_sources.0.source', 'homepage')
            ->assertJsonPath('data.marketing_insights.lead_sources.0.total', 2)
            ->assertJsonPath('data.marketing_insights.popular_searches.0.label_en', 'Men Shoes')
            ->assertJsonPath('data.marketing_insights.campaign_sections.0.key', 'eid-campaign')
            ->assertJsonStructure(['data' => ['summary', 'sales_trend', 'order_status', 'payment_status', 'top_products', 'stock_risk', 'coupon_performance', 'marketing_insights']]);
    }

    public function test_report_period_must_be_supported(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson('/api/admin/reports?period=365')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['period']);
    }

    public function test_customer_cannot_view_admin_reports(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)->getJson('/api/admin/reports')->assertForbidden();
    }
}
