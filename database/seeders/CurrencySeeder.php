<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            'KZT' => [
                'name'           => 'Kazakhstani Tenge',
                'symbol'         => '₸',
                'decimal_places' => 2,
            ],
            'USD' => [
                'name'           => 'US Dollar',
                'symbol'         => '$',
                'decimal_places' => 2,
            ],
            'EUR' => [
                'name'           => 'Euro',
                'symbol'         => '€',
                'decimal_places' => 2,
            ],
            'RUB' => [
                'name'           => 'Russian Ruble',
                'symbol'         => '₽',
                'decimal_places' => 2,
            ],
        ];

        foreach ($currencies as $code => $attributes) {
            Currency::updateOrCreate(
                ['code' => $code],
                [
                    ...$attributes,
                    'is_active' => true,
                ],
            );
        }
    }
}
