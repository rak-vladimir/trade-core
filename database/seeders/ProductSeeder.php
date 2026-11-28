<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::query()->pluck('id', 'name');
        $units = Unit::query()->pluck('id', 'code');
        $currencies = Currency::query()->pluck('id', 'code');

        $products = [
            [
                'sku'         => 'DEMO-LAPTOP-001',
                'name'        => 'Business Laptop 15',
                'category'    => 'Ноутбуки',
                'unit'        => 'piece',
                'currency'    => 'USD',
                'price'       => 850.0000,
                'description' => 'Business laptop with 15-inch display.',
            ],
            [
                'sku'         => 'DEMO-LAPTOP-002',
                'name'        => 'Ultrabook Pro 14',
                'category'    => 'Ноутбуки',
                'unit'        => 'piece',
                'currency'    => 'USD',
                'price'       => 1200.0000,
                'description' => 'Lightweight professional laptop.',
            ],
            [
                'sku'         => 'DEMO-COMP-001',
                'name'        => 'SSD 1TB',
                'category'    => 'Комплектующие',
                'unit'        => 'piece',
                'currency'    => 'USD',
                'price'       => 95.0000,
                'description' => '1TB solid-state drive.',
            ],
            [
                'sku'         => 'DEMO-COMP-002',
                'name'        => 'DDR5 RAM 16GB',
                'category'    => 'Комплектующие',
                'unit'        => 'piece',
                'currency'    => 'USD',
                'price'       => 65.0000,
                'description' => '16GB DDR5 memory module.',
            ],
            [
                'sku'         => 'DEMO-PHONE-001',
                'name'        => 'Smartphone 128GB',
                'category'    => 'Телефоны',
                'unit'        => 'piece',
                'currency'    => 'USD',
                'price'       => 420.0000,
                'description' => 'Smartphone with 128GB storage.',
            ],
            [
                'sku'         => 'DEMO-PHONE-002',
                'name'        => 'Smartphone 256GB',
                'category'    => 'Телефоны',
                'unit'        => 'piece',
                'currency'    => 'USD',
                'price'       => 560.0000,
                'description' => 'Smartphone with 256GB storage.',
            ],
            [
                'sku'         => 'DEMO-PERIPH-001',
                'name'        => 'Wireless Keyboard',
                'category'    => 'Периферия',
                'unit'        => 'piece',
                'currency'    => 'KZT',
                'price'       => 12500.0000,
                'description' => 'Wireless office keyboard.',
            ],
            [
                'sku'         => 'DEMO-PERIPH-002',
                'name'        => 'Wireless Mouse',
                'category'    => 'Периферия',
                'unit'        => 'piece',
                'currency'    => 'KZT',
                'price'       => 7500.0000,
                'description' => 'Wireless optical mouse.',
            ],
            [
                'sku'         => 'DEMO-STATIONERY-001',
                'name'        => 'A4 Paper',
                'category'    => 'Канцелярия',
                'unit'        => 'pack',
                'currency'    => 'KZT',
                'price'       => 2500.0000,
                'description' => 'A4 office paper, 500 sheets.',
            ],
            [
                'sku'         => 'DEMO-STATIONERY-002',
                'name'        => 'Ballpoint Pens',
                'category'    => 'Канцелярия',
                'unit'        => 'box',
                'currency'    => 'KZT',
                'price'       => 1800.0000,
                'description' => 'Box of 50 ballpoint pens.',
            ],
            [
                'sku'         => 'DEMO-CONSUMABLE-001',
                'name'        => 'Printer Toner',
                'category'    => 'Расходные материалы',
                'unit'        => 'piece',
                'currency'    => 'KZT',
                'price'       => 14500.0000,
                'description' => 'Black laser printer toner.',
            ],
            [
                'sku'         => 'DEMO-LIGHT-001',
                'name'        => 'LED Bulb 12W',
                'category'    => 'Освещение',
                'unit'        => 'piece',
                'currency'    => 'KZT',
                'price'       => 1200.0000,
                'description' => 'Energy-efficient LED bulb.',
            ],
            [
                'sku'         => 'DEMO-LIGHT-002',
                'name'        => 'LED Strip 5m',
                'category'    => 'Освещение',
                'unit'        => 'meter',
                'currency'    => 'KZT',
                'price'       => 1800.0000,
                'description' => 'LED lighting strip.',
            ],
            [
                'sku'         => 'DEMO-TOOLS-001',
                'name'        => 'Cordless Drill',
                'category'    => 'Инструменты',
                'unit'        => 'piece',
                'currency'    => 'EUR',
                'price'       => 110.0000,
                'description' => 'Cordless electric drill.',
            ],
            [
                'sku'         => 'DEMO-TOOLS-002',
                'name'        => 'Screwdriver Set',
                'category'    => 'Инструменты',
                'unit'        => 'box',
                'currency'    => 'EUR',
                'price'       => 35.0000,
                'description' => 'Multi-purpose screwdriver set.',
            ],
        ];

        foreach ($products as $product) {
            Product::withTrashed()->updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'category_id' => $categories->get($product['category']),
                    'unit_id'     => $units->get($product['unit']),
                    'currency_id' => $currencies->get($product['currency']),
                    'name'        => $product['name'],
                    'description' => $product['description'],
                    'price'       => $product['price'],
                    'is_active'   => true,
                    'deleted_at'  => null,
                ],
            );
        }
    }
}
