<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\NewsletterLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNewsletterLeadManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_filter_and_update_newsletter_leads(): void
    {
        $admin = User::factory()->admin()->create();
        $lead = NewsletterLead::factory()->create([
            'email' => 'nadia@example.test',
            'phone' => '01700000000',
            'source' => 'homepage',
            'interest' => 'fashion',
            'is_active' => true,
        ]);
        NewsletterLead::factory()->inactive()->create([
            'email' => 'hidden@example.test',
            'interest' => 'beauty',
        ]);

        $list = $this->actingAs($admin)->getJson('/api/admin/newsletter-leads?q=nadia&status=active&source=homepage');

        $list->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'nadia@example.test')
            ->assertJsonPath('data.0.interest', 'fashion');

        $update = $this->actingAs($admin)->patchJson('/api/admin/newsletter-leads/'.$lead->id, [
            'is_active' => false,
            'admin_note' => 'Requested fewer Eid campaign messages.',
        ]);

        $update->assertOk()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.admin_note', 'Requested fewer Eid campaign messages.');

        $this->assertDatabaseHas('newsletter_leads', [
            'id' => $lead->id,
            'is_active' => false,
            'admin_note' => 'Requested fewer Eid campaign messages.',
        ]);
    }

    public function test_customer_cannot_manage_newsletter_leads(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $lead = NewsletterLead::factory()->create();

        $this->actingAs($customer)->getJson('/api/admin/newsletter-leads')->assertForbidden();
        $this->actingAs($customer)->patchJson('/api/admin/newsletter-leads/'.$lead->id, [
            'is_active' => false,
        ])->assertForbidden();
    }
}
