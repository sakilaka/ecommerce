<?php

namespace Tests\Feature;

use App\Enums\HomepageSectionType;
use App\Models\AuditLog;
use App\Models\HomepageSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminHomepageSectionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_filter_homepage_sections(): void
    {
        $admin = User::factory()->admin()->create();
        $section = HomepageSection::factory()->create([
            'key' => 'eid-mega-sale-hero',
            'type' => HomepageSectionType::Hero,
            'title' => 'Eid Mega Sale Hero',
            'is_active' => true,
        ]);
        HomepageSection::factory()->create(['key' => 'hidden-banner', 'type' => HomepageSectionType::PromoBanner, 'is_active' => false]);

        $response = $this->actingAs($admin)->getJson('/api/admin/homepage-sections?q=eid&type=hero&active=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $section->id)
            ->assertJsonPath('data.0.key', 'eid-mega-sale-hero')
            ->assertJsonPath('data.0.type', 'hero')
            ->assertJsonStructure(['data' => [['id', 'key', 'type', 'title', 'is_active', 'sort_order', 'content', 'settings']], 'links', 'meta']);
    }

    public function test_admin_can_create_homepage_section(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/homepage-sections', [
            'key' => 'boishakh-promo',
            'type' => 'promo_banner',
            'title' => 'Boishakh Promo',
            'is_active' => true,
            'sort_order' => 25,
            'content' => [
                'en' => ['eyebrow' => 'Pohela Boishakh', 'title' => 'Pohela Boishakh Edit', 'subtitle' => 'Festive fashion and gifts.', 'highlight' => 'Curated festive picks', 'cta' => 'Shop Now'],
                'bn' => ['eyebrow' => 'পহেলা বৈশাখ', 'title' => 'পহেলা বৈশাখ কালেকশন', 'subtitle' => 'Festive fashion এবং gifts.', 'highlight' => 'Curated festive picks', 'cta' => 'শপ করুন'],
            ],
            'settings' => ['cta_url' => '/collections/pohela-boishakh', 'image' => '/images/premium-hero.png', 'tone' => 'cream-maroon', 'placement' => 'home_midpage'],
            'starts_at' => now()->subDay()->toISOString(),
            'ends_at' => now()->addWeek()->toISOString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.key', 'boishakh-promo')
            ->assertJsonPath('data.type', 'promo_banner')
            ->assertJsonPath('data.content.en.highlight', 'Curated festive picks')
            ->assertJsonPath('data.settings.placement', 'home_midpage');

        $this->assertDatabaseHas('homepage_sections', ['key' => 'boishakh-promo', 'type' => 'promo_banner']);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'event' => 'homepage_section.created',
            'auditable_type' => HomepageSection::class,
        ]);
    }

    public function test_admin_can_update_homepage_section(): void
    {
        $admin = User::factory()->admin()->create();
        $section = HomepageSection::factory()->create(['key' => 'winter-sale', 'is_active' => true]);

        $response = $this->actingAs($admin)->patchJson('/api/admin/homepage-sections/'.$section->id, [
            'key' => 'winter-sale',
            'type' => 'hero',
            'title' => 'Winter Sale Updated',
            'is_active' => false,
            'sort_order' => 12,
            'content' => [
                'en' => ['title' => 'Winter essentials'],
                'bn' => ['title' => 'শীতের প্রয়োজনীয় পণ্য'],
            ],
            'settings' => ['cta_url' => '/collections/winter'],
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Winter Sale Updated')
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.sort_order', 12);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'event' => 'homepage_section.updated',
            'auditable_type' => HomepageSection::class,
            'auditable_id' => $section->id,
        ]);
        $this->assertSame(false, AuditLog::query()->where('event', 'homepage_section.updated')->first()?->new_values['is_active']);
    }

    public function test_homepage_section_validation_rejects_invalid_payload(): void
    {
        $admin = User::factory()->admin()->create();
        HomepageSection::factory()->create(['key' => 'duplicate-key']);

        $response = $this->actingAs($admin)->postJson('/api/admin/homepage-sections', [
            'key' => 'duplicate-key',
            'type' => 'unknown',
            'is_active' => 'yes please',
            'sort_order' => -1,
            'content' => ['en' => []],
            'ends_at' => now()->subDay()->toISOString(),
            'starts_at' => now()->toISOString(),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['key', 'type', 'is_active', 'sort_order', 'content.bn', 'ends_at']);
    }

    public function test_admin_can_reorder_homepage_sections(): void
    {
        $admin = User::factory()->admin()->create();
        $hero = HomepageSection::factory()->create(['key' => 'hero', 'sort_order' => 10]);
        $flashSale = HomepageSection::factory()->create(['key' => 'flash-sale', 'sort_order' => 20]);
        $bestSellers = HomepageSection::factory()->create(['key' => 'best-sellers', 'sort_order' => 30]);

        $response = $this->actingAs($admin)->patchJson('/api/admin/homepage-sections/reorder', [
            'sections' => [
                ['id' => $bestSellers->id, 'sort_order' => 10],
                ['id' => $hero->id, 'sort_order' => 20],
                ['id' => $flashSale->id, 'sort_order' => 30],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.0.id', $bestSellers->id)
            ->assertJsonPath('data.1.id', $hero->id)
            ->assertJsonPath('data.2.id', $flashSale->id);

        $this->assertDatabaseHas('homepage_sections', ['id' => $bestSellers->id, 'sort_order' => 10]);
        $this->assertDatabaseHas('homepage_sections', ['id' => $hero->id, 'sort_order' => 20]);
        $this->assertDatabaseHas('homepage_sections', ['id' => $flashSale->id, 'sort_order' => 30]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'event' => 'homepage_section.reordered',
            'auditable_type' => HomepageSection::class,
        ]);
    }

    public function test_homepage_section_reorder_validation_rejects_duplicate_sections(): void
    {
        $admin = User::factory()->admin()->create();
        $section = HomepageSection::factory()->create();

        $response = $this->actingAs($admin)->patchJson('/api/admin/homepage-sections/reorder', [
            'sections' => [
                ['id' => $section->id, 'sort_order' => 10],
                ['id' => $section->id, 'sort_order' => 20],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['sections.0.id', 'sections.1.id']);
    }

    public function test_customer_cannot_manage_homepage_sections(): void
    {
        $customer = User::factory()->create();
        $section = HomepageSection::factory()->create();

        $this->actingAs($customer)->getJson('/api/admin/homepage-sections')->assertForbidden();
        $this->actingAs($customer)->postJson('/api/admin/homepage-sections', [])->assertForbidden();
        $this->actingAs($customer)->patchJson('/api/admin/homepage-sections/'.$section->id, [])->assertForbidden();
        $this->actingAs($customer)->patchJson('/api/admin/homepage-sections/reorder', [])->assertForbidden();
    }
}
