<?php

namespace Tests\Feature;

use App\Models\NewsletterLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontNewsletterLeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_can_capture_newsletter_offer_lead(): void
    {
        $response = $this->postJson('/api/storefront/newsletter-leads', [
            'email' => 'nadia@example.test',
            'phone' => '01700000000',
            'source' => 'homepage',
            'locale' => 'bn',
            'interest' => 'offers-newsletter',
            'consent' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'nadia@example.test')
            ->assertJsonPath('data.phone', '01700000000')
            ->assertJsonPath('data.locale', 'bn')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('newsletter_leads', [
            'email' => 'nadia@example.test',
            'phone' => '01700000000',
            'source' => 'homepage',
            'interest' => 'offers-newsletter',
            'is_active' => true,
        ]);
    }

    public function test_newsletter_lead_requires_contact_and_consent(): void
    {
        $response = $this->postJson('/api/storefront/newsletter-leads', [
            'email' => null,
            'phone' => null,
            'consent' => false,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'phone', 'consent']);
    }

    public function test_existing_email_lead_is_reactivated_instead_of_duplicated(): void
    {
        NewsletterLead::factory()->inactive()->create([
            'email' => 'buyer@example.test',
            'phone' => null,
            'source' => 'homepage',
        ]);

        $response = $this->postJson('/api/storefront/newsletter-leads', [
            'email' => 'buyer@example.test',
            'source' => 'homepage',
            'locale' => 'en',
            'interest' => 'eid-offers',
            'consent' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.email', 'buyer@example.test')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseCount('newsletter_leads', 1);
        $this->assertDatabaseHas('newsletter_leads', [
            'email' => 'buyer@example.test',
            'interest' => 'eid-offers',
            'is_active' => true,
        ]);
    }
}
