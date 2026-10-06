<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_after_repeated_failed_attempts(): void
    {
        RateLimiter::clear('buyer@example.com|127.0.0.1');
        User::factory()->create([
            'email' => 'buyer@example.com',
            'password' => Hash::make('password123'),
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/auth/login', [
                'login' => 'buyer@example.com',
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/auth/login', [
            'login' => 'buyer@example.com',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_newsletter_lead_capture_is_rate_limited(): void
    {
        RateLimiter::clear('127.0.0.1');

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/storefront/newsletter-leads', [
                'email' => 'buyer'.$attempt.'@example.test',
                'source' => 'homepage',
                'locale' => 'en',
                'interest' => 'eid-offers',
                'consent' => true,
            ])->assertCreated();
        }

        $this->postJson('/api/storefront/newsletter-leads', [
            'email' => 'buyer6@example.test',
            'source' => 'homepage',
            'locale' => 'en',
            'interest' => 'eid-offers',
            'consent' => true,
        ])->assertTooManyRequests();
    }
}
