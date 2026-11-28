<?php

namespace Api\V1\Stock;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementIndexTest extends TestCase
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

    public function test_guest_cannot_list_stock_movements(): void
    {
        $this->getJson('/api/v1/stock-movements')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_stock_movements(): void
    {
        $movement = StockMovement::factory()->create([
            'type' => 'receipt',
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/stock-movements')
            ->assertOk()
            ->assertJsonPath('data.0.id', $movement->id)
            ->assertJsonPath('data.0.type', 'receipt')
            ->assertJsonPath('data.0.product.id', $movement->product_id);
    }

    public function test_movements_can_be_filtered_by_type(): void
    {
        $receipt = StockMovement::factory()->receipt()->create();

        StockMovement::factory()->writeOff()->create();

        StockMovement::factory()->transfer()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/stock-movements?type=receipt')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $receipt->id);
    }

    public function test_movements_can_be_filtered_by_product(): void
    {
        $product = Product::factory()->create();

        $movement = StockMovement::factory()->create([
            'product_id' => $product->id,
        ]);

        StockMovement::factory()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/v1/stock-movements?product_id={$product->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $movement->id);
    }

    public function test_movements_can_be_filtered_by_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();

        $movement = StockMovement::factory()->create([
            'type'            => 'receipt',
            'to_warehouse_id' => $warehouse->id,
        ]);

        StockMovement::factory()->create([
            'type' => 'receipt',
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/v1/stock-movements?warehouse_id={$warehouse->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $movement->id);
    }

    public function test_invalid_filters_return_validation_error(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/stock-movements?type=invalid')
            ->assertUnprocessable();
    }

    public function test_movements_are_paginated(): void
    {
        StockMovement::factory()->count(3)->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/stock-movements?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }
}
