<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number'       => 'ORD-' . fake()->unique()->numerify('########'),
            'customer_id'  => Customer::factory(),
            'currency_id'  => Currency::query()->firstOrFail()->id,
            'warehouse_id' => Warehouse::factory(),
            'user_id'      => User::factory(),
            'status'       => OrderStatus::Draft,
            'total_amount' => 0,
            'comment'      => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn() => [
            'status' => OrderStatus::Confirmed,
        ]);
    }

    public function shipped(): static
    {
        return $this->state(fn() => [
            'status' => OrderStatus::Shipped,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn() => [
            'status' => OrderStatus::Cancelled,
        ]);
    }
}
