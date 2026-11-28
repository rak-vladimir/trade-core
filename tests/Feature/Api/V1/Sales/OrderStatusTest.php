<?php

namespace Tests\Feature\Api\V1\Sales;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTest extends TestCase
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

    public function test_confirmed_order_can_be_shipped_without_deducting_stock_again(): void
    {
        [$order, $stock] = $this->createOrderWithItem();

        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/ship")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::Shipped->value);

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => OrderStatus::Shipped->value,
        ]);

        $this->assertDatabaseHas('stocks', [
            'id'       => $stock->id,
            'quantity' => '3.0000',
        ]);

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_confirmed_order_can_be_cancelled_and_stock_is_restored(): void
    {
        [$order, $stock, $product] = $this->createOrderWithItem();

        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::Cancelled->value);

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => OrderStatus::Cancelled->value,
        ]);

        $this->assertDatabaseHas('stocks', [
            'id'       => $stock->id,
            'quantity' => '5.0000',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id'        => $product->id,
            'from_warehouse_id' => null,
            'to_warehouse_id'   => $order->warehouse_id,
            'type'              => 'receipt',
            'quantity'          => '2.0000',
            'reference'         => $order->number,
            'user_id'           => $user->id,
        ]);

        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_draft_order_can_be_cancelled_without_restoring_stock(): void
    {
        [$order, $stock] = $this->createOrderWithItem(OrderStatus::Draft);

        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::Cancelled->value);

        $this->assertDatabaseHas('stocks', [
            'id'       => $stock->id,
            'quantity' => '3.0000',
        ]);

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_order_cannot_be_shipped_or_cancelled_more_than_once(): void
    {
        [$order, $stock] = $this->createOrderWithItem();

        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/ship")
            ->assertOk();

        $this->postJson("/api/v1/orders/{$order->id}/ship")
            ->assertUnprocessable();

        $this->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertUnprocessable();

        $this->assertDatabaseHas('stocks', [
            'id'       => $stock->id,
            'quantity' => '3.0000',
        ]);

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_draft_order_cannot_be_shipped(): void
    {
        [$order] = $this->createOrderWithItem(OrderStatus::Draft);

        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/ship")
            ->assertUnprocessable();

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => OrderStatus::Draft->value,
        ]);
    }

    public function test_shipped_and_cancelled_orders_cannot_change_status(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum');

        $shippedOrder = Order::factory()->shipped()->create();

        $this->postJson("/api/v1/orders/{$shippedOrder->id}/ship")
            ->assertUnprocessable();

        $this->postJson("/api/v1/orders/{$shippedOrder->id}/cancel")
            ->assertUnprocessable();

        $cancelledOrder = Order::factory()->cancelled()->create();

        $this->postJson("/api/v1/orders/{$cancelledOrder->id}/ship")
            ->assertUnprocessable();

        $this->postJson("/api/v1/orders/{$cancelledOrder->id}/cancel")
            ->assertUnprocessable();
    }

    public function test_guests_cannot_change_order_status(): void
    {
        $order = Order::factory()->confirmed()->create();

        $this->postJson("/api/v1/orders/{$order->id}/ship")
            ->assertUnauthorized();

        $this->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertUnauthorized();
    }

    private function createOrderWithItem(OrderStatus $status = OrderStatus::Confirmed): array
    {
        $order = Order::factory()
            ->state(['status' => $status])
            ->create();

        $product = Product::factory()->create();

        OrderItem::factory()->create([
            'order_id'    => $order->id,
            'product_id'  => $product->id,
            'quantity'    => '2.0000',
            'unit_price'  => '100.0000',
            'total_price' => '200.0000',
        ]);

        $stock = Stock::factory()->create([
            'warehouse_id' => $order->warehouse_id,
            'product_id'   => $product->id,
            'quantity'     => '3.0000',
        ]);

        return [$order, $stock, $product];
    }

    public function test_cancelling_order_restores_stock_for_all_items(): void
    {
        [$order, $firstStock, $firstProduct] = $this->createOrderWithItem();

        $secondProduct = Product::factory()->create();

        $secondStock = Stock::factory()->create([
            'warehouse_id' => $order->warehouse_id,
            'product_id'   => $secondProduct->id,
            'quantity'     => '7.0000',
        ]);

        OrderItem::factory()->create([
            'order_id'    => $order->id,
            'product_id'  => $secondProduct->id,
            'quantity'    => '3.0000',
            'unit_price'  => '50.0000',
            'total_price' => '150.0000',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::Cancelled->value);

        $this->assertDatabaseHas('stocks', [
            'id'       => $firstStock->id,
            'quantity' => '5.0000',
        ]);

        $this->assertDatabaseHas('stocks', [
            'id'       => $secondStock->id,
            'quantity' => '10.0000',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $firstProduct->id,
            'type'       => 'receipt',
            'quantity'   => '2.0000',
            'reference'  => $order->number,
            'user_id'    => $user->id,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $secondProduct->id,
            'type'       => 'receipt',
            'quantity'   => '3.0000',
            'reference'  => $order->number,
            'user_id'    => $user->id,
        ]);

        $this->assertDatabaseCount('stock_movements', 2);
    }
}
