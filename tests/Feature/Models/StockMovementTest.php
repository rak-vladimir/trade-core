<?php

namespace Tests\Feature\Models;

use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementTest extends TestCase
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

    public function test_receipt_movement_can_be_created(): void
    {
        $movement = StockMovement::factory()->create([
            'type'              => 'receipt',
            'from_warehouse_id' => null,
            'to_warehouse_id'   => Warehouse::factory(),
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'id'                => $movement->id,
            'type'              => 'receipt',
            'from_warehouse_id' => null,
            'to_warehouse_id'   => $movement->to_warehouse_id,
        ]);
    }

    public function test_write_off_movement_can_be_created(): void
    {
        $movement = StockMovement::factory()->create([
            'type'              => 'write_off',
            'from_warehouse_id' => Warehouse::factory(),
            'to_warehouse_id'   => null,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'id'                => $movement->id,
            'type'              => 'write_off',
            'from_warehouse_id' => $movement->from_warehouse_id,
            'to_warehouse_id'   => null,
        ]);
    }

    public function test_transfer_movement_can_be_created(): void
    {
        $movement = StockMovement::factory()->create([
            'type'              => 'transfer',
            'from_warehouse_id' => Warehouse::factory(),
            'to_warehouse_id'   => Warehouse::factory(),
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'id'   => $movement->id,
            'type' => 'transfer',
        ]);

        $this->assertNotSame(
            $movement->from_warehouse_id,
            $movement->to_warehouse_id,
        );
    }

    public function test_movement_quantity_must_be_positive(): void
    {
        $movement = StockMovement::factory()->create();

        $this->expectException(QueryException::class);

        $movement->update([
            'quantity' => 0,
        ]);
    }

    public function test_movement_type_must_be_valid(): void
    {
        $movement = StockMovement::factory()->create();

        $this->expectException(QueryException::class);

        $movement->update([
            'type' => 'invalid',
        ]);
    }

    public function test_transfer_cannot_use_same_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();

        $movement = StockMovement::factory()->create();

        $this->expectException(QueryException::class);

        $movement->update([
            'type'              => 'transfer',
            'from_warehouse_id' => $warehouse->id,
            'to_warehouse_id'   => $warehouse->id,
        ]);
    }
}
