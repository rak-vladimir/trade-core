<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = Currency::query()
            ->pluck('id', 'code');

        $effectiveAt = '2026-01-01 00:00:00';

        // Illustrative demo rates only. Not real market rates.
        $rates = [
            ['USD', 'KZT', 500.0000000000],
            ['EUR', 'KZT', 550.0000000000],
            ['RUB', 'KZT', 5.0000000000],
            ['USD', 'EUR', 0.9200000000],
            ['USD', 'RUB', 90.0000000000],
            ['EUR', 'USD', 1.0900000000],
        ];

        foreach ($rates as [$base, $quote, $rate]) {
            ExchangeRate::updateOrCreate(
                [
                    'base_currency_id'  => $currencies->get($base),
                    'quote_currency_id' => $currencies->get($quote),
                    'effective_at'      => $effectiveAt,
                ],
                [
                    'rate' => $rate,
                ],
            );
        }
    }
}
