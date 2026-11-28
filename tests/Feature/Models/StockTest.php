<?php

namespace Tests\Feature\Models;

use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            \Database\Seeders\UnitSeeder::class,
            \Database\Seeders\CurrencySeeder::class,
        ]);
    }

    public function test_stock_can_be_created(): void
    {
        $stock = Stock::factory()->create([
            'quantity' => 125.5000,
        ]);

        $this->assertDatabaseHas('stocks', [
            'id'           => $stock->id,
            'warehouse_id' => $stock->warehouse_id,
            'product_id'   => $stock->product_id,
            'quantity'     => '125.5000',
        ]);
    }

    public function test_stock_belongs_to_warehouse_and_product(): void
    {
        $stock = Stock::factory()->create();

        $this->assertInstanceOf(
            Warehouse::class,
            $stock->warehouse,
        );

        $this->assertInstanceOf(
            Product::class,
            $stock->product,
        );
    }

    public function test_stock_quantity_cannot_be_negative(): void
    {
        $stock = Stock::factory()->create();

        $this->expectException(QueryException::class);

        $stock->update([
            'quantity' => -1,
        ]);
    }

    public function test_product_and_warehouse_combination_must_be_unique(): void
    {
        $stock = Stock::factory()->create();

        $this->expectException(QueryException::class);

        Stock::factory()->create([
            'warehouse_id' => $stock->warehouse_id,
            'product_id'   => $stock->product_id,
        ]);
    }
}
