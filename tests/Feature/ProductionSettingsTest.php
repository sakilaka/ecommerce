<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionSettingsTest extends TestCase
{
    public function test_production_security_headers_are_added_in_production(): void
    {
        $this->app['env'] = 'production';

        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(self)');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
    }

    public function test_production_security_headers_are_skipped_outside_production(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeaderMissing('X-Content-Type-Options');
        $response->assertHeaderMissing('X-Frame-Options');
        $response->assertHeaderMissing('Referrer-Policy');
        $response->assertHeaderMissing('Permissions-Policy');
        $response->assertHeaderMissing('Cross-Origin-Opener-Policy');
    }
}
