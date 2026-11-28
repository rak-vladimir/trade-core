<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            [
                'code'    => 'WH-001',
                'name'    => 'Основной склад',
                'address' => 'Алматы',
            ],
            [
                'code'    => 'WH-002',
                'name'    => 'Резервный склад',
                'address' => 'Алматы',
            ],
            [
                'code'    => 'WH-003',
                'name'    => 'Магазин',
                'address' => 'Алматы',
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::updateOrCreate(
                ['code' => $warehouse['code']],
                $warehouse,
            );
        }
    }
}
