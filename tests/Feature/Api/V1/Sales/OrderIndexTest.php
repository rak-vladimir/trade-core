<?php

namespace Tests\Feature\Api\V1\Sales;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderIndexTest extends TestCase
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

    public function test_authenticated_user_can_list_orders(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/orders');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $order->id)
            ->assertJsonPath('data.0.number', $order->number)
            ->assertJsonPath('data.0.customer.id', $order->customer_id)
            ->assertJsonPath('data.0.warehouse.id', $order->warehouse_id)
            ->assertJsonPath('data.0.currency.id', $order->currency_id);
    }

    public function test_orders_can_be_searched_by_number(): void
    {
        $user = User::factory()->create();

        $matchingOrder = Order::factory()->create([
            'number' => 'ORD-20261127-ABC123',
        ]);

        Order::factory()->create([
            'number' => 'ORD-20261127-XYZ789',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/orders?search=ABC123');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingOrder->id);
    }

    public function test_orders_can_be_filtered_by_customer(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()->create();

        $matchingOrder = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        Order::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/orders?customer_id={$customer->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingOrder->id);
    }

    public function test_orders_can_be_filtered_by_warehouse(): void
    {
        $user = User::factory()->create();

        $warehouse = Warehouse::factory()->create();

        $matchingOrder = Order::factory()->create([
            'warehouse_id' => $warehouse->id,
        ]);

        Order::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/orders?warehouse_id={$warehouse->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingOrder->id);
    }

    public function test_orders_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();

        $matchingOrder = Order::factory()->create([
            'status' => 'confirmed',
        ]);

        Order::factory()->create([
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/orders?status=confirmed');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingOrder->id);
    }

    public function test_orders_can_be_filtered_by_date_range(): void
    {
        $user = User::factory()->create();

        $matchingOrder = Order::factory()->create([
            'created_at' => '2026-11-15 12:00:00',
        ]);

        Order::factory()->create([
            'created_at' => '2026-10-15 12:00:00',
        ]);

        Order::factory()->create([
            'created_at' => '2026-12-15 12:00:00',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/orders?date_from=2026-11-01&date_to=2026-11-30');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingOrder->id);
    }

    public function test_orders_can_be_sorted_and_paginated(): void
    {
        $user = User::factory()->create();

        Order::factory()->create([
            'number'       => 'ORD-001',
            'total_amount' => '100.0000',
        ]);

        $secondOrder = Order::factory()->create([
            'number'       => 'ORD-002',
            'total_amount' => '200.0000',
        ]);

        Order::factory()->create([
            'number'       => 'ORD-003',
            'total_amount' => '50.0000',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/orders?sort=total_amount&direction=desc&per_page=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $secondOrder->id)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/orders?status=unknown&sort=invalid');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
                'sort',
            ]);
    }

    public function test_guest_cannot_list_orders(): void
    {
        $response = $this->getJson('/api/v1/orders');

        $response->assertUnauthorized();
    }

    public function test_pagination_is_stable_when_sort_values_are_equal(): void
    {
        $user = User::factory()->create();

        $firstOrder = Order::factory()->create([
            'total_amount' => '100.0000',
        ]);

        $secondOrder = Order::factory()->create([
            'total_amount' => '100.0000',
        ]);

        $thirdOrder = Order::factory()->create([
            'total_amount' => '100.0000',
        ]);

        $firstPage = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/orders?sort=total_amount&direction=desc&per_page=1');

        $firstPage->assertOk()
            ->assertJsonPath('data.0.id', $thirdOrder->id)
            ->assertJsonPath('meta.total', 3);

        $secondPage = $this->getJson(
            '/api/v1/orders?sort=total_amount&direction=desc&per_page=1&page=2'
        );

        $secondPage->assertOk()
            ->assertJsonPath('data.0.id', $secondOrder->id);

        $thirdPage = $this->getJson(
            '/api/v1/orders?sort=total_amount&direction=desc&per_page=1&page=3'
        );

        $thirdPage->assertOk()
            ->assertJsonPath('data.0.id', $firstOrder->id);
    }

    public function test_orders_can_be_searched_by_number_case_insensitively(): void
    {
        $matchingOrder = Order::factory()->create([
            'number' => 'ORD-20261127-ABC123',
        ]);

        Order::factory()->create([
            'number' => 'ORD-20261127-XYZ789',
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/orders?search=abc123')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingOrder->id);
    }

    public function test_orders_can_be_filtered_by_date_from_only(): void
    {
        $matchingOrder = Order::factory()->create([
            'created_at' => '2026-11-15 12:00:00',
        ]);

        Order::factory()->create([
            'created_at' => '2026-10-15 12:00:00',
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/orders?date_from=2026-11-01')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingOrder->id);
    }

    public function test_orders_can_be_filtered_by_date_to_only(): void
    {
        $matchingOrder = Order::factory()->create([
            'created_at' => '2026-11-15 12:00:00',
        ]);

        Order::factory()->create([
            'created_at' => '2026-12-15 12:00:00',
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/orders?date_to=2026-11-30')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingOrder->id);
    }

    public function test_orders_can_be_sorted_by_number_ascending(): void
    {
        Order::factory()->create(['number' => 'ORD-003']);
        Order::factory()->create(['number' => 'ORD-001']);
        Order::factory()->create(['number' => 'ORD-002']);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/orders?sort=number&direction=asc')
            ->assertOk()
            ->assertJsonPath('data.0.number', 'ORD-001')
            ->assertJsonPath('data.1.number', 'ORD-002')
            ->assertJsonPath('data.2.number', 'ORD-003');
    }

    public function test_orders_can_be_sorted_by_status(): void
    {
        Order::factory()->create(['status' => 'shipped']);
        Order::factory()->create(['status' => 'cancelled']);
        Order::factory()->create(['status' => 'confirmed']);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/orders?sort=status&direction=asc')
            ->assertOk()
            ->assertJsonPath('data.0.status', 'cancelled')
            ->assertJsonPath('data.1.status', 'confirmed')
            ->assertJsonPath('data.2.status', 'shipped');
    }

    public function test_orders_index_returns_empty_result_when_no_orders_match(): void
    {
        Order::factory()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/orders?search=NONEXISTENT')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_invalid_index_filters_are_rejected(): void
    {
        $invalidQueries = [
            '?search=' . str_repeat('a', 31),
            '?customer_id=abc',
            '?warehouse_id=abc',
            '?status=unknown',
            '?date_from=invalid',
            '?date_to=2026-10-01&date_from=2026-11-01',
            '?sort=invalid',
            '?direction=random',
            '?per_page=0',
            '?per_page=101',
        ];

        foreach ($invalidQueries as $query) {
            $this->actingAs(User::factory()->create(), 'sanctum')
                ->getJson('/api/v1/orders' . $query)
                ->assertUnprocessable();
        }
    }
}
