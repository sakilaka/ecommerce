<?php

namespace Tests\Feature;

use App\Enums\ProductReviewStatus;
use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_active_published_products(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $brand = Brand::factory()->create(['is_active' => true]);
        $publishedProduct = Product::factory()->for($category)->for($brand)->create([
            'name' => 'Published Phone',
            'status' => ProductStatus::Active,
            'published_at' => now()->subMinute(),
            'seo_title' => 'Smartphone Online Bangladesh',
            'seo_description' => 'Buy smartphones with verified service support.',
        ]);
        Product::factory()->for($category)->for($brand)->draft()->create(['name' => 'Draft Phone']);
        Product::factory()->for($category)->for($brand)->create([
            'name' => 'Future Phone',
            'status' => ProductStatus::Active,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/storefront/products');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $publishedProduct->id)
            ->assertJsonCount(1, 'data');
    }

    public function test_filters_products_by_category_brand_and_price(): void
    {
        $category = Category::factory()->create(['slug' => 'electronics', 'is_active' => true, 'seo_title' => 'Electronics Bangladesh', 'seo_description' => 'Shop electronics with nationwide delivery.']);
        $otherCategory = Category::factory()->create(['slug' => 'fashion', 'is_active' => true]);
        $brand = Brand::factory()->create(['slug' => 'samsung', 'is_active' => true, 'seo_title' => 'Samsung Official Store', 'seo_description' => 'Shop Samsung products online.']);
        $otherBrand = Brand::factory()->create(['slug' => 'xiaomi', 'is_active' => true]);
        $matchedProduct = Product::factory()->for($category)->for($brand)->create([
            'name' => 'Matched Phone',
            'price' => 150000,
            'status' => ProductStatus::Active,
            'published_at' => now()->subMinute(),
        ]);
        Product::factory()->for($otherCategory)->for($brand)->create([
            'name' => 'Wrong Category',
            'price' => 150000,
            'status' => ProductStatus::Active,
            'published_at' => now()->subMinute(),
        ]);
        Product::factory()->for($category)->for($otherBrand)->create([
            'name' => 'Wrong Brand',
            'price' => 150000,
            'status' => ProductStatus::Active,
            'published_at' => now()->subMinute(),
        ]);
        Product::factory()->for($category)->for($brand)->create([
            'name' => 'Too Expensive',
            'price' => 300000,
            'status' => ProductStatus::Active,
            'published_at' => now()->subMinute(),
        ]);

        $response = $this->getJson('/api/storefront/products?category=electronics&brand=samsung&price_max=1999');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $matchedProduct->id)
            ->assertJsonPath('data.0.category.seo.title', 'Electronics Bangladesh')
            ->assertJsonPath('data.0.brand.seo.title', 'Samsung Official Store')
            ->assertJsonCount(1, 'data');
    }

    public function test_filters_campaign_and_topic_links(): void
    {
        $category = Category::factory()->create(['name' => 'Electronics', 'slug' => 'electronics', 'is_active' => true]);
        $brand = Brand::factory()->create(['name' => 'Samsung', 'slug' => 'samsung', 'is_active' => true]);
        $discountedEarbuds = Product::factory()->for($category)->for($brand)->create([
            'name' => 'Wireless Earbuds Pro',
            'short_description' => 'Compact audio for daily commute.',
            'price' => 169900,
            'compare_at_price' => 225000,
            'status' => ProductStatus::Active,
            'published_at' => now()->subMinute(),
        ]);
        Product::factory()->for($category)->for($brand)->create([
            'name' => 'Full Price Phone',
            'short_description' => 'No sale price available.',
            'price' => 2400000,
            'compare_at_price' => null,
            'status' => ProductStatus::Active,
            'published_at' => now()->subMinute(),
        ]);

        $flashSaleResponse = $this->getJson('/api/storefront/products?campaign=flash-sale');
        $topicResponse = $this->getJson('/api/storefront/products?topic=wireless-earbuds');

        $flashSaleResponse->assertOk()
            ->assertJsonPath('data.0.id', $discountedEarbuds->id)
            ->assertJsonCount(1, 'data');
        $topicResponse->assertOk()
            ->assertJsonPath('data.0.id', $discountedEarbuds->id)
            ->assertJsonCount(1, 'data');
    }

    public function test_returns_only_approved_public_product_reviews(): void
    {
        $product = Product::factory()->create([
            'status' => ProductStatus::Active,
            'published_at' => now()->subMinute(),
        ]);
        ProductReview::factory()->for($product)->approved()->verifiedPurchase()->create([
            'rating' => 5,
            'title' => 'Excellent delivery',
            'body' => 'Reached Dhaka quickly with clean packaging.',
            'customer_name' => 'Nadia Rahman',
            'customer_email' => 'nadia@example.test',
            'customer_phone' => '01700000000',
            'admin_note' => 'Internal moderation note',
        ]);
        ProductReview::factory()->for($product)->create([
            'status' => ProductReviewStatus::Pending,
            'rating' => 1,
            'title' => 'Pending review',
        ]);

        $response = $this->getJson('/api/storefront/products/'.$product->slug.'/reviews');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.rating', 5)
            ->assertJsonPath('data.0.title', 'Excellent delivery')
            ->assertJsonPath('data.0.customer_name', 'Nadia Rahman')
            ->assertJsonMissingPath('data.0.customer_email')
            ->assertJsonMissingPath('data.0.customer_phone')
            ->assertJsonMissingPath('data.0.admin_note')
            ->assertJsonPath('summary.average_rating', 5)
            ->assertJsonPath('summary.reviews_count', 1)
            ->assertJsonPath('summary.verified_count', 1);
    }

    public function test_returns_product_detail_with_only_active_variants(): void
    {
        $product = Product::factory()->create([
            'status' => ProductStatus::Active,
            'published_at' => now()->subMinute(),
            'seo_title' => 'Smartphone Online Bangladesh',
            'seo_description' => 'Buy smartphones with verified service support.',
        ]);
        $activeVariant = ProductVariant::factory()->for($product)->create([
            'name' => 'Black / 128GB',
            'is_active' => true,
        ]);
        ProductVariant::factory()->for($product)->create([
            'name' => 'Hidden Variant',
            'is_active' => false,
        ]);
        ProductImage::factory()->for($product)->primary()->create([
            'path' => '/images/category-collage.png',
        ]);

        $response = $this->getJson('/api/storefront/products/'.$product->slug);

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.variants.0.id', $activeVariant->id)
            ->assertJsonPath('data.seo.title', 'Smartphone Online Bangladesh')
            ->assertJsonPath('data.seo.description', 'Buy smartphones with verified service support.')
            ->assertJsonCount(1, 'data.variants')
            ->assertJsonPath('data.images.0.path', '/images/category-collage.png');
    }

    public function test_hides_unpublished_product_detail(): void
    {
        $product = Product::factory()->draft()->create();

        $response = $this->getJson('/api/storefront/products/'.$product->slug);

        $response->assertNotFound();
    }

    public function test_returns_homepage_card_metadata(): void
    {
        $category = Category::factory()->create(['name' => 'Electronics', 'is_active' => true]);
        $brand = Brand::factory()->create(['name' => 'Samsung', 'is_active' => true]);
        $product = Product::factory()->for($category)->for($brand)->create([
            'status' => ProductStatus::Active,
            'published_at' => now()->subMinute(),
            'meta' => [
                'badge' => 'Official',
                'rating' => '4.8',
                'reviews' => '1.4K',
                'sold' => '620 sold',
                'image_position' => '0% 0%',
            ],
        ]);
        ProductImage::factory()->for($product)->primary()->create([
            'path' => '/images/category-collage.png',
            'alt_text' => 'Premium electronics bundle',
        ]);

        $response = $this->getJson('/api/storefront/products');

        $response->assertOk()
            ->assertJsonPath('data.0.badge', 'Official')
            ->assertJsonPath('data.0.rating', '4.8')
            ->assertJsonPath('data.0.reviews', '1.4K')
            ->assertJsonPath('data.0.sold', '620 sold')
            ->assertJsonPath('data.0.image_position', '0% 0%')
            ->assertJsonPath('data.0.primary_image.path', '/images/category-collage.png');
    }
}
