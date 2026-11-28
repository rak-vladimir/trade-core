<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            'piece'        => 0,
            'kilogram'     => 3,
            'gram'         => 0,
            'liter'        => 3,
            'meter'        => 3,
            'square_meter' => 2,
            'cubic_meter'  => 3,
            'pack'         => 0,
            'box'          => 0,
        ];

        foreach ($units as $code => $decimalPlaces) {
            Unit::updateOrCreate(
                ['code' => $code],
                [
                    'decimal_places' => $decimalPlaces,
                    'is_active'      => true,
                ],
            );
        }
    }
}
