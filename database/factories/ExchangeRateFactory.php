<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;
use RuntimeException;

/**
 * @extends Factory<ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currencies = Currency::query()->inRandomOrder()->limit(2)->get();

        if ($currencies->count() < 2) {
            throw new RuntimeException('At least two currencies are required.');
        }

        return [
            'base_currency_id'  => $currencies[0]->id,
            'quote_currency_id' => $currencies[1]->id,
            'rate'              => fake()->randomFloat(10, 0.1, 1000),
            'effective_at'      => now(),
        ];
    }
}
