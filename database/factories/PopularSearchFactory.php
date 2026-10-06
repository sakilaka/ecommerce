<?php

namespace Database\Factories;

use App\Models\PopularSearch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PopularSearch>
 */
class PopularSearchFactory extends Factory
{
    public function definition(): array
    {
        $keyword = fake()->words(2, true);

        return [
            'label_en' => Str::headline($keyword),
            'label_bn' => null,
            'keyword' => $keyword,
            'href' => '/products?q='.rawurlencode($keyword),
            'sort_order' => fake()->numberBetween(1, 40),
            'click_count' => fake()->numberBetween(0, 500),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
