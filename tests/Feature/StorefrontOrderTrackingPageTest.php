<?php

namespace Tests\Feature;

use Tests\TestCase;

class StorefrontOrderTrackingPageTest extends TestCase
{
    public function test_order_tracking_page_is_available(): void
    {
        $this->get('/order-tracking')
            ->assertOk()
            ->assertViewIs('welcome');
    }
}
