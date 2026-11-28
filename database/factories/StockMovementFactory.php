<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id'        => Product::factory(),
            'from_warehouse_id' => null,
            'to_warehouse_id'   => Warehouse::factory(),
            'type'              => 'receipt',
            'quantity'          => fake()->randomFloat(4, 0.0001, 1000),
            'reference'         => fake()->optional()->bothify('REF-####'),
            'comment'           => fake()->optional()->sentence(),
            'user_id'           => User::factory(),
        ];
    }

    public function receipt(): static
    {
        return $this->state([
            'type'              => 'receipt',
            'from_warehouse_id' => null,
            'to_warehouse_id'   => Warehouse::factory(),
        ]);
    }

    public function writeOff(): static
    {
        return $this->state([
            'type'              => 'write_off',
            'from_warehouse_id' => Warehouse::factory(),
            'to_warehouse_id'   => null,
        ]);
    }

    public function transfer(): static
    {
        return $this->state([
            'type'              => 'transfer',
            'from_warehouse_id' => Warehouse::factory(),
            'to_warehouse_id'   => Warehouse::factory(),
        ]);
    }
}
