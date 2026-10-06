<?php

namespace Database\Factories;

use App\Models\NewsletterLead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterLead>
 */
class NewsletterLeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->safeEmail(),
            'phone' => '017'.fake()->numerify('########'),
            'source' => 'homepage',
            'locale' => 'en',
            'interest' => fake()->randomElement(['eid-offers', 'electronics', 'fashion', 'beauty']),
            'consented_at' => now(),
            'last_subscribed_at' => now(),
            'is_active' => true,
            'admin_note' => null,
            'meta' => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
