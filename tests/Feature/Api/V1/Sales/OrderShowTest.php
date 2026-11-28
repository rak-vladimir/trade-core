<?php

namespace Tests\Feature\Api\V1\Sales;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderShowTest extends TestCase
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

    public function test_authenticated_user_can_view_order_with_all_related_fields(): void
    {
        $order = Order::factory()->create([
            'total_amount' => '125.0000',
            'comment'      => 'Priority delivery',
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.number', $order->number)
            ->assertJsonPath('data.status', $order->status->value)
            ->assertJsonPath('data.total_amount', '125.0000')
            ->assertJsonPath('data.comment', 'Priority delivery')
            ->assertJsonPath('data.customer.id', $order->customer_id)
            ->assertJsonPath('data.customer.name', $order->customer->name)
            ->assertJsonPath('data.currency.id', $order->currency_id)
            ->assertJsonPath('data.currency.code', $order->currency->code)
            ->assertJsonPath('data.warehouse.id', $order->warehouse_id)
            ->assertJsonPath('data.warehouse.code', $order->warehouse->code)
            ->assertJsonPath('data.warehouse.name', $order->warehouse->name)
            ->assertJsonPath('data.user.id', $order->user_id)
            ->assertJsonPath('data.user.name', $order->user->name)
            ->assertJsonPath('data.items', [])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'number',
                    'status',
                    'total_amount',
                    'comment',
                    'customer'  => ['id', 'name'],
                    'currency'  => ['id', 'code'],
                    'warehouse' => ['id', 'code', 'name'],
                    'user'      => ['id', 'name'],
                    'items',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_order_response_contains_items_and_product_fields(): void
    {
        $order = Order::factory()->create();

        $product = Product::factory()->create([
            'price' => '15.0000',
        ]);

        $item = OrderItem::factory()->create([
            'order_id'    => $order->id,
            'product_id'  => $product->id,
            'quantity'    => '3.0000',
            'unit_price'  => '15.0000',
            'total_price' => '45.0000',
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $item->id)
            ->assertJsonPath('data.items.0.product.id', $product->id)
            ->assertJsonPath('data.items.0.product.sku', $product->sku)
            ->assertJsonPath('data.items.0.product.name', $product->name)
            ->assertJsonPath('data.items.0.quantity', '3.0000')
            ->assertJsonPath('data.items.0.unit_price', '15.0000')
            ->assertJsonPath('data.items.0.total_price', '45.0000')
            ->assertJsonMissingPath('data.items.0.product.price')
            ->assertJsonMissingPath('data.items.0.product.description');
    }

    public function test_order_response_contains_multiple_items(): void
    {
        $order = Order::factory()->create();

        $firstProduct = Product::factory()->create();
        $secondProduct = Product::factory()->create();

        $firstItem = OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $firstProduct->id,
        ]);

        $secondItem = OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $secondProduct->id,
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data.items')
            ->assertJsonFragment([
                'id'  => $firstItem->id,
                'sku' => $firstProduct->sku,
            ])
            ->assertJsonFragment([
                'id'  => $secondItem->id,
                'sku' => $secondProduct->sku,
            ]);
    }

    public function test_order_not_found_returns_404(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/orders/999999')
            ->assertNotFound();
    }

    public function test_guest_cannot_view_order(): void
    {
        $order = Order::factory()->create();

        $this->getJson("/api/v1/orders/{$order->id}")
            ->assertUnauthorized();
    }
}
