<?php

namespace Tests\Feature\Api\V1\Stock;

use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStockBalanceTest extends TestCase
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

    public function test_product_stock_balance_returns_total_and_warehouse_breakdown(): void
    {
        $product = Product::factory()->create();

        $mainWarehouse = Warehouse::factory()->create();
        $reserveWarehouse = Warehouse::factory()->create();

        Stock::factory()->create([
            'product_id'   => $product->id,
            'warehouse_id' => $mainWarehouse->id,
            'quantity'     => '100.0000',
        ]);

        Stock::factory()->create([
            'product_id'   => $product->id,
            'warehouse_id' => $reserveWarehouse->id,
            'quantity'     => '50.0000',
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/v1/products/{$product->id}/stock-balance")
            ->assertOk()
            ->assertJsonPath('data.product_id', $product->id)
            ->assertJsonPath('data.total_quantity', '150.0000')
            ->assertJsonCount(2, 'data.warehouses')
            ->assertJsonFragment([
                'id'       => $mainWarehouse->id,
                'code'     => $mainWarehouse->code,
                'name'     => $mainWarehouse->name,
                'quantity' => '100.0000',
            ])
            ->assertJsonFragment([
                'id'       => $reserveWarehouse->id,
                'code'     => $reserveWarehouse->code,
                'name'     => $reserveWarehouse->name,
                'quantity' => '50.0000',
            ]);
    }

    public function test_product_without_stock_returns_zero_balance(): void
    {
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/v1/products/{$product->id}/stock-balance")
            ->assertOk()
            ->assertJsonPath('data.product_id', $product->id)
            ->assertJsonPath('data.total_quantity', '0.0000')
            ->assertJsonCount(0, 'data.warehouses');
    }

    public function test_guest_cannot_view_product_stock_balance(): void
    {
        $product = Product::factory()->create();

        $this->getJson("/api/v1/products/{$product->id}/stock-balance")
            ->assertUnauthorized();
    }

    public function test_nonexistent_product_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/products/999999/stock-balance')
            ->assertNotFound();
    }
}
