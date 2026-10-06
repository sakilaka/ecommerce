<?php

namespace Database\Factories;

use App\Enums\HomepageSectionType;
use App\Models\HomepageSection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HomepageSection>
 */
class HomepageSectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'key' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'type' => HomepageSectionType::Hero->value,
            'title' => Str::title($title),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 50),
            'content' => [
                'en' => ['title' => Str::title($title), 'subtitle' => fake()->sentence()],
                'bn' => ['title' => Str::title($title), 'subtitle' => fake()->sentence()],
            ],
            'settings' => ['cta_url' => '/products'],
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
        ]);
    }
}
