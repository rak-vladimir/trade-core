<?php

namespace Tests\Feature\Api\V1\Sales;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStoreTest extends TestCase
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

    public function test_guest_cannot_create_order(): void
    {
        $response = $this->postJson('/api/v1/orders', []);

        $response->assertUnauthorized();

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_order_can_be_created_and_stock_is_deducted(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create([
            'price' => '12.5000',
        ]);

        Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => '10.0000',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'customer_id'  => $customer->id,
                'warehouse_id' => $warehouse->id,
                'items'        => [
                    [
                        'product_id' => $product->id,
                        'quantity'   => '2.0000',
                    ],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.currency.id', $product->currency_id)
            ->assertJsonPath('data.items.0.product.id', $product->id);

        $order = Order::query()->firstOrFail();

        $this->assertDatabaseHas('orders', [
            'id'           => $order->id,
            'customer_id'  => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id'      => $user->id,
            'currency_id'  => $product->currency_id,
            'status'       => 'confirmed',
            'total_amount' => '25.0000',
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id'    => $order->id,
            'product_id'  => $product->id,
            'quantity'    => '2.0000',
            'unit_price'  => '12.5000',
            'total_price' => '25.0000',
        ]);

        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => '8.0000',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id'        => $product->id,
            'from_warehouse_id' => $warehouse->id,
            'to_warehouse_id'   => null,
            'type'              => 'write_off',
            'quantity'          => '2.0000',
            'reference'         => $order->number,
            'user_id'           => $user->id,
        ]);
    }

    public function test_order_cannot_be_created_when_stock_is_insufficient(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => '1.0000',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'customer_id'  => $customer->id,
                'warehouse_id' => $warehouse->id,
                'items'        => [
                    [
                        'product_id' => $product->id,
                        'quantity'   => '2.0000',
                    ],
                ],
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);

        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => '1.0000',
        ]);
    }

    public function test_order_cannot_contain_products_with_different_currencies(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $currencies = Currency::query()
            ->orderBy('id')
            ->take(2)
            ->get();

        $this->assertCount(2, $currencies);

        $firstProduct = Product::factory()->create([
            'currency_id' => $currencies[0]->id,
        ]);

        $secondProduct = Product::factory()->create([
            'currency_id' => $currencies[1]->id,
        ]);

        foreach ([$firstProduct, $secondProduct] as $product) {
            Stock::factory()->create([
                'warehouse_id' => $warehouse->id,
                'product_id'   => $product->id,
                'quantity'     => '10.0000',
            ]);
        }

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'customer_id'  => $customer->id,
                'warehouse_id' => $warehouse->id,
                'items'        => [
                    [
                        'product_id' => $firstProduct->id,
                        'quantity'   => '1.0000',
                    ],
                    [
                        'product_id' => $secondProduct->id,
                        'quantity'   => '1.0000',
                    ],
                ],
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);

        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $firstProduct->id,
            'quantity'     => '10.0000',
        ]);

        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $secondProduct->id,
            'quantity'     => '10.0000',
        ]);
    }

    public function test_order_cannot_be_created_for_inactive_customer(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->inactive()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'customer_id'  => $customer->id,
                'warehouse_id' => $warehouse->id,
                'items'        => [
                    [
                        'product_id' => $product->id,
                        'quantity'   => '1.0000',
                    ],
                ],
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_cannot_be_created_for_inactive_warehouse(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create([
            'is_active' => false,
        ]);
        $product = Product::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'customer_id'  => $customer->id,
                'warehouse_id' => $warehouse->id,
                'items'        => [
                    [
                        'product_id' => $product->id,
                        'quantity'   => '1.0000',
                    ],
                ],
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_cannot_be_created_with_inactive_product(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'customer_id'  => $customer->id,
                'warehouse_id' => $warehouse->id,
                'items'        => [
                    [
                        'product_id' => $product->id,
                        'quantity'   => '1.0000',
                    ],
                ],
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_cannot_be_created_when_item_total_exceeds_decimal_limit(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $product = Product::factory()->create([
            'price' => '9999999999999999.9999',
        ]);

        Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => '2.0000',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'customer_id'  => $customer->id,
                'warehouse_id' => $warehouse->id,
                'items'        => [
                    [
                        'product_id' => $product->id,
                        'quantity'   => '2.0000',
                    ],
                ],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);

        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => '2.0000',
        ]);
    }

    public function test_order_cannot_be_created_when_order_total_exceeds_decimal_limit(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $currency = Currency::query()->firstOrFail();

        $products = Product::factory()->count(2)->create([
            'currency_id' => $currency->id,
            'price'       => '6000000000000000.0000',
        ]);

        foreach ($products as $product) {
            Stock::factory()->create([
                'warehouse_id' => $warehouse->id,
                'product_id'   => $product->id,
                'quantity'     => '1.0000',
            ]);
        }

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'customer_id'  => $customer->id,
                'warehouse_id' => $warehouse->id,
                'items'        => $products->map(fn(Product $product) => [
                    'product_id' => $product->id,
                    'quantity'   => '1.0000',
                ])->all(),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);

        foreach ($products as $product) {
            $this->assertDatabaseHas('stocks', [
                'warehouse_id' => $warehouse->id,
                'product_id'   => $product->id,
                'quantity'     => '1.0000',
            ]);
        }
    }

    public function test_order_cannot_be_created_without_stock_record(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'customer_id'  => $customer->id,
                'warehouse_id' => $warehouse->id,
                'items'        => [
                    [
                        'product_id' => $product->id,
                        'quantity'   => '1.0000',
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseMissing('stocks', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
        ]);
    }

    public function test_order_creation_is_atomic_when_one_of_multiple_products_has_insufficient_stock(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $currency = Currency::query()->firstOrFail();

        $firstProduct = Product::factory()->create([
            'currency_id' => $currency->id,
        ]);

        $secondProduct = Product::factory()->create([
            'currency_id' => $currency->id,
        ]);

        Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $firstProduct->id,
            'quantity'     => '10.0000',
        ]);

        Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $secondProduct->id,
            'quantity'     => '1.0000',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'customer_id'  => $customer->id,
                'warehouse_id' => $warehouse->id,
                'items'        => [
                    [
                        'product_id' => $firstProduct->id,
                        'quantity'   => '2.0000',
                    ],
                    [
                        'product_id' => $secondProduct->id,
                        'quantity'   => '2.0000',
                    ],
                ],
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);

        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $firstProduct->id,
            'quantity'     => '10.0000',
        ]);

        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $secondProduct->id,
            'quantity'     => '1.0000',
        ]);
    }

    public function test_order_cannot_contain_duplicate_products(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => '10.0000',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'customer_id'  => $customer->id,
                'warehouse_id' => $warehouse->id,
                'items'        => [
                    [
                        'product_id' => $product->id,
                        'quantity'   => '2.0000',
                    ],
                    [
                        'product_id' => $product->id,
                        'quantity'   => '3.0000',
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.1.product_id');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);

        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => '10.0000',
        ]);
    }

    public function test_order_rejects_invalid_quantities(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => '10.0000',
        ]);

        foreach ([
                     '0',
                     '-1',
                     '1.00000',
                     '10000000000000000',
                     'invalid',
                 ] as $quantity) {
            $this->actingAs($user, 'sanctum')
                ->postJson('/api/v1/orders', [
                    'customer_id'  => $customer->id,
                    'warehouse_id' => $warehouse->id,
                    'items'        => [
                        [
                            'product_id' => $product->id,
                            'quantity'   => $quantity,
                        ],
                    ],
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('items.0.quantity');
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);

        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => '10.0000',
        ]);
    }

    public function test_order_can_be_created_with_minimum_quantity_precision(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create([
            'price' => '100.0000',
        ]);

        Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => '1.0000',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'customer_id'  => $customer->id,
                'warehouse_id' => $warehouse->id,
                'items'        => [
                    [
                        'product_id' => $product->id,
                        'quantity'   => '0.0001',
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.items.0.quantity', '0.0001')
            ->assertJsonPath('data.items.0.total_price', '0.0100');

        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => '0.9999',
        ]);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('stock_movements', 1);
    }
}
