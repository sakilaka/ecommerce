<?php

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Enums\HomepageSectionType;
use App\Models\Coupon;
use App\Models\HomepageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontHomepageSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_lists_only_active_scheduled_homepage_sections(): void
    {
        $visible = HomepageSection::factory()->create([
            'key' => 'visible-hero',
            'type' => HomepageSectionType::Hero,
            'is_active' => true,
            'sort_order' => 5,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);
        HomepageSection::factory()->inactive()->create(['key' => 'inactive-hero']);
        HomepageSection::factory()->expired()->create(['key' => 'expired-hero']);
        HomepageSection::factory()->create(['key' => 'future-hero', 'starts_at' => now()->addDay()]);

        $response = $this->getJson('/api/storefront/homepage-sections');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.key', $visible->key)
            ->assertJsonStructure(['data' => [['key', 'type', 'title', 'sort_order', 'content', 'settings']]]);
    }

    public function test_storefront_can_filter_active_campaign_banners(): void
    {
        Coupon::factory()->create([
            'code' => 'EID50',
            'type' => DiscountType::Percent,
            'value' => 50,
            'minimum_order_amount' => 199900,
            'maximum_discount_amount' => 100000,
            'usage_limit' => 100,
            'used_count' => 5,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
            'is_active' => true,
        ]);

        $visible = HomepageSection::factory()->create([
            'key' => 'eid-campaign-banner',
            'type' => HomepageSectionType::PromoBanner,
            'is_active' => true,
            'sort_order' => 20,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'content' => [
                'en' => ['eyebrow' => 'Eid Mega Sale', 'title' => 'Save up to 50%', 'highlight' => 'Up to 50% Off', 'cta' => 'Shop Now'],
                'bn' => ['eyebrow' => 'ঈদ মেগা সেল', 'title' => '৫০% পর্যন্ত ছাড়', 'highlight' => '৫০% পর্যন্ত ছাড়', 'cta' => 'শপ করুন'],
            ],
            'settings' => ['cta_url' => '/products?campaign=eid-mega-sale', 'image' => '/images/premium-hero.png', 'placement' => 'home_midpage', 'coupon_code' => 'EID50'],
        ]);
        HomepageSection::factory()->inactive()->create(['key' => 'inactive-campaign-banner', 'type' => HomepageSectionType::PromoBanner]);
        HomepageSection::factory()->create(['key' => 'active-hero', 'type' => HomepageSectionType::Hero]);

        $response = $this->getJson('/api/storefront/homepage-sections?type=promo_banner');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.key', $visible->key)
            ->assertJsonPath('data.0.type', 'promo_banner')
            ->assertJsonPath('data.0.content.en.highlight', 'Up to 50% Off')
            ->assertJsonPath('data.0.settings.placement', 'home_midpage')
            ->assertJsonPath('data.0.coupon.code', 'EID50')
            ->assertJsonPath('data.0.coupon.remaining_uses', 95);
    }

    public function test_storefront_hides_inactive_campaign_coupon_details(): void
    {
        Coupon::factory()->create([
            'code' => 'EXPIRED10',
            'type' => DiscountType::Percent,
            'value' => 10,
            'minimum_order_amount' => 0,
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->subDay(),
            'is_active' => true,
        ]);
        HomepageSection::factory()->create([
            'key' => 'expired-coupon-banner',
            'type' => HomepageSectionType::PromoBanner,
            'settings' => ['cta_url' => '/products?campaign=expired', 'coupon_code' => 'EXPIRED10'],
        ]);

        $response = $this->getJson('/api/storefront/homepage-sections?type=promo_banner');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.key', 'expired-coupon-banner')
            ->assertJsonPath('data.0.coupon', null);
    }

    public function test_storefront_can_filter_homepage_sections_by_type(): void
    {
        HomepageSection::factory()->create(['key' => 'active-hero', 'type' => HomepageSectionType::Hero]);
        HomepageSection::factory()->create(['key' => 'active-announcement', 'type' => HomepageSectionType::Announcement]);

        $response = $this->getJson('/api/storefront/homepage-sections?type=announcement');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.key', 'active-announcement')
            ->assertJsonPath('data.0.type', 'announcement');
    }
}
