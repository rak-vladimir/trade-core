<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = Warehouse::query()
            ->whereIn('code', ['WH-001', 'WH-002', 'WH-003'])
            ->get()
            ->keyBy('code');

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        foreach ($products as $index => $product) {
            Stock::firstOrCreate(
                [
                    'warehouse_id' => $warehouses['WH-001']->id,
                    'product_id'   => $product->id,
                ],
                [
                    'quantity' => 20 + ($index * 5),
                ],
            );

            if ($index % 2 === 0) {
                Stock::firstOrCreate(
                    [
                        'warehouse_id' => $warehouses['WH-002']->id,
                        'product_id'   => $product->id,
                    ],
                    [
                        'quantity' => 5 + $index,
                    ],
                );
            }

            if ($index % 3 === 0) {
                Stock::firstOrCreate(
                    [
                        'warehouse_id' => $warehouses['WH-003']->id,
                        'product_id'   => $product->id,
                    ],
                    [
                        'quantity' => 2 + $index,
                    ],
                );
            }
        }
    }
}
