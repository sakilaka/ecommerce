<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_support_ticket_with_order_context(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['order_number' => 'ORD-SUPPORT-1']);

        $response = $this->actingAs($user)->postJson('/api/customer/support-tickets', [
            'order_id' => $order->id,
            'topic' => 'delivery',
            'priority' => 'high',
            'subject' => 'Delivery address update',
            'message' => 'Please call me before dispatching this order.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.topic', 'delivery')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.order.order_number', 'ORD-SUPPORT-1');

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'topic' => 'delivery',
            'priority' => 'high',
            'subject' => 'Delivery address update',
        ]);
    }

    public function test_customer_can_list_only_their_support_tickets(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = SupportTicket::factory()->for($user)->create(['ticket_number' => 'SUP-CUSTOMER-1']);
        SupportTicket::factory()->for($otherUser)->create(['ticket_number' => 'SUP-OTHER-1']);

        $response = $this->actingAs($user)->getJson('/api/customer/support-tickets');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ticket->id)
            ->assertJsonPath('data.0.ticket_number', 'SUP-CUSTOMER-1');
    }

    public function test_customer_cannot_attach_another_customers_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = Order::factory()->for($otherUser)->create();

        $response = $this->actingAs($user)->postJson('/api/customer/support-tickets', [
            'order_id' => $order->id,
            'topic' => 'order_help',
            'subject' => 'Need order help',
            'message' => 'Please help with this order issue.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('order_id');
    }

    public function test_support_ticket_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/customer/support-tickets', [
            'topic' => 'unknown',
            'subject' => 'No',
            'message' => 'Short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['topic', 'subject', 'message']);
    }

    public function test_guest_cannot_manage_support_tickets(): void
    {
        $this->getJson('/api/customer/support-tickets')->assertUnauthorized();
        $this->postJson('/api/customer/support-tickets', [
            'topic' => 'delivery',
            'subject' => 'Delivery support',
            'message' => 'Please help me with delivery.',
        ])->assertUnauthorized();
    }
}
