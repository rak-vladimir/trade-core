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

class StockMovementStoreTest extends TestCase
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

    public function test_receipt_increases_stock_and_creates_movement(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        Stock::factory()->create([
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => '10.0000',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/stock-movements/receipts', [
                'product_id'      => $product->id,
                'to_warehouse_id' => $warehouse->id,
                'quantity'        => '5.2500',
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'receipt')
            ->assertJsonPath('data.quantity', '5.2500');

        $this->assertDatabaseHas('stocks', [
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => '15.2500',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id'        => $product->id,
            'from_warehouse_id' => null,
            'to_warehouse_id'   => $warehouse->id,
            'type'              => 'receipt',
            'quantity'          => '5.2500',
            'user_id'           => $user->id,
        ]);
    }

    public function test_receipt_creates_stock_if_it_does_not_exist(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/stock-movements/receipts', [
                'product_id'      => $product->id,
                'to_warehouse_id' => $warehouse->id,
                'quantity'        => '7.0000',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('stocks', [
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => '7.0000',
        ]);
    }

    public function test_write_off_decreases_stock_and_creates_movement(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        Stock::factory()->create([
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => '10.0000',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/stock-movements/write-offs', [
                'product_id'        => $product->id,
                'from_warehouse_id' => $warehouse->id,
                'quantity'          => '3.5000',
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'write_off');

        $this->assertDatabaseHas('stocks', [
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => '6.5000',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id'        => $product->id,
            'from_warehouse_id' => $warehouse->id,
            'to_warehouse_id'   => null,
            'type'              => 'write_off',
            'quantity'          => '3.5000',
            'user_id'           => $user->id,
        ]);
    }

    public function test_write_off_is_rejected_when_stock_is_insufficient(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        Stock::factory()->create([
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => '2.0000',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/stock-movements/write-offs', [
                'product_id'        => $product->id,
                'from_warehouse_id' => $warehouse->id,
                'quantity'          => '5.0000',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->assertDatabaseHas('stocks', [
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity'     => '2.0000',
        ]);

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_write_off_is_rejected_when_stock_does_not_exist(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/stock-movements/write-offs', [
                'product_id'        => $product->id,
                'from_warehouse_id' => $warehouse->id,
                'quantity'          => '1.0000',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->assertDatabaseCount('stocks', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_transfer_moves_stock_between_warehouses(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $source = Warehouse::factory()->create();
        $destination = Warehouse::factory()->create();

        Stock::factory()->create([
            'product_id'   => $product->id,
            'warehouse_id' => $source->id,
            'quantity'     => '10.0000',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/stock-movements/transfers', [
                'product_id'        => $product->id,
                'from_warehouse_id' => $source->id,
                'to_warehouse_id'   => $destination->id,
                'quantity'          => '4.0000',
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'transfer');

        $this->assertDatabaseHas('stocks', [
            'product_id'   => $product->id,
            'warehouse_id' => $source->id,
            'quantity'     => '6.0000',
        ]);

        $this->assertDatabaseHas('stocks', [
            'product_id'   => $product->id,
            'warehouse_id' => $destination->id,
            'quantity'     => '4.0000',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id'        => $product->id,
            'from_warehouse_id' => $source->id,
            'to_warehouse_id'   => $destination->id,
            'type'              => 'transfer',
            'quantity'          => '4.0000',
            'user_id'           => $user->id,
        ]);
    }

    public function test_transfer_is_rejected_when_stock_is_insufficient(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $source = Warehouse::factory()->create();
        $destination = Warehouse::factory()->create();

        Stock::factory()->create([
            'product_id'   => $product->id,
            'warehouse_id' => $source->id,
            'quantity'     => '2.0000',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/stock-movements/transfers', [
                'product_id'        => $product->id,
                'from_warehouse_id' => $source->id,
                'to_warehouse_id'   => $destination->id,
                'quantity'          => '5.0000',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->assertDatabaseHas('stocks', [
            'product_id'   => $product->id,
            'warehouse_id' => $source->id,
            'quantity'     => '2.0000',
        ]);

        $this->assertDatabaseMissing('stocks', [
            'product_id'   => $product->id,
            'warehouse_id' => $destination->id,
        ]);

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_transfer_between_same_warehouses_is_rejected(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/stock-movements/transfers', [
                'product_id'        => $product->id,
                'from_warehouse_id' => $warehouse->id,
                'to_warehouse_id'   => $warehouse->id,
                'quantity'          => '1.0000',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to_warehouse_id');

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_guests_cannot_create_stock_movements(): void
    {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $payload = [
            'product_id'      => $product->id,
            'to_warehouse_id' => $warehouse->id,
            'quantity'        => '1.0000',
        ];

        $this->postJson('/api/v1/stock-movements/receipts', $payload)
            ->assertUnauthorized();

        $this->assertDatabaseCount('stock_movements', 0);
    }
}
