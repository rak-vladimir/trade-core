<?php

namespace Api\V1\Stock;

use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            UnitSeeder::class,
            CurrencySeeder::class,
        ]);
    }

    public function test_guest_cannot_list_stocks(): void
    {
        $this->getJson('/api/v1/stocks')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_stocks(): void
    {
        $stock = Stock::factory()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/stocks')
            ->assertOk()
            ->assertJsonPath('data.0.id', $stock->id)
            ->assertJsonPath('data.0.warehouse.id', $stock->warehouse_id)
            ->assertJsonPath('data.0.product.id', $stock->product_id)
            ->assertJsonPath('data.0.quantity', $stock->quantity);
    }

    public function test_stocks_can_be_filtered_by_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();

        $stock = Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
        ]);

        Stock::factory()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/v1/stocks?warehouse_id={$warehouse->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $stock->id);
    }

    public function test_stocks_can_be_filtered_by_product(): void
    {
        $product = Product::factory()->create();

        $stock = Stock::factory()->create([
            'product_id' => $product->id,
        ]);

        Stock::factory()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/v1/stocks?product_id={$product->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $stock->id);
    }

    public function test_stocks_can_be_searched_by_product_name(): void
    {
        $product = Product::factory()->create([
            'name' => 'Wireless Keyboard',
        ]);

        $stock = Stock::factory()->create([
            'product_id' => $product->id,
        ]);

        Stock::factory()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/stocks?search=Wireless')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $stock->id);
    }

    public function test_stocks_can_be_searched_by_product_sku(): void
    {
        $product = Product::factory()->create([
            'sku' => 'KEYBOARD-001',
        ]);

        $stock = Stock::factory()->create([
            'product_id' => $product->id,
        ]);

        Stock::factory()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/stocks?search=KEYBOARD-001')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $stock->id);
    }

    public function test_stocks_can_be_sorted_by_quantity(): void
    {
        $warehouse = Warehouse::factory()->create();

        $lowStock = Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'quantity'     => 10,
        ]);

        $highStock = Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'quantity'     => 100,
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/stocks?sort=quantity&direction=asc')
            ->assertOk()
            ->assertJsonPath('data.0.id', $lowStock->id)
            ->assertJsonPath('data.1.id', $highStock->id);
    }

    public function test_invalid_filters_return_validation_error(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/stocks?warehouse_id=999999')
            ->assertUnprocessable();
    }

    public function test_stocks_are_paginated(): void
    {
        Stock::factory()->count(3)->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/stocks?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }
}
