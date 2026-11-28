<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            UnitSeeder::class,
            CurrencySeeder::class,
            CategorySeeder::class,
            ExchangeRateSeeder::class,
            ProductSeeder::class,
            WarehouseSeeder::class,
            StockSeeder::class,
        ]);
    }
}
