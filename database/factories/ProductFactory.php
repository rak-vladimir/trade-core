<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'unit_id'     => Unit::query()->inRandomOrder()->value('id')
                ?? throw new \RuntimeException('No units available.'),
            'currency_id' => Currency::query()->inRandomOrder()->value('id')
                ?? throw new \RuntimeException('No currencies available.'),
            'sku'         => fake()->unique()->bothify('PRD-########'),
            'name'        => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'price'       => fake()->randomFloat(4, 1, 100000),
            'is_active'   => true,
        ];
    }
}
