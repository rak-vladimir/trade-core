<?php

namespace Tests\Feature\Models;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            \Database\Seeders\UnitSeeder::class,
            \Database\Seeders\CurrencySeeder::class,
        ]);
    }

    public function test_warehouse_can_be_created(): void
    {
        $warehouse = Warehouse::factory()->create([
            'code' => 'WH-TEST',
            'name' => 'Тестовый склад',
        ]);

        $this->assertDatabaseHas('warehouses', [
            'id'        => $warehouse->id,
            'code'      => 'WH-TEST',
            'name'      => 'Тестовый склад',
            'is_active' => true,
        ]);
    }

    public function test_warehouse_code_must_be_unique(): void
    {
        Warehouse::factory()->create([
            'code' => 'WH-UNIQUE',
        ]);

        $this->expectException(QueryException::class);

        Warehouse::factory()->create([
            'code' => 'WH-UNIQUE',
        ]);
    }

    public function test_warehouse_has_stocks(): void
    {
        $warehouse = Warehouse::factory()->create();
        $stock = Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
        ]);

        $this->assertTrue(
            $warehouse->stocks->contains($stock),
        );
    }

    public function test_warehouse_has_incoming_and_outgoing_movements(): void
    {
        $source = Warehouse::factory()->create();
        $destination = Warehouse::factory()->create();

        $movement = StockMovement::factory()->create([
            'from_warehouse_id' => $source->id,
            'to_warehouse_id'   => $destination->id,
            'type'              => 'transfer',
        ]);

        $this->assertTrue(
            $source->outgoingMovements->contains($movement),
        );

        $this->assertTrue(
            $destination->incomingMovements->contains($movement),
        );
    }
}
