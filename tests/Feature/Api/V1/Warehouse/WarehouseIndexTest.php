<?php

namespace Tests\Feature\Api\V1\Warehouse;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_warehouses(): void
    {
        $this->getJson('/api/v1/warehouses')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_active_warehouses_by_default(): void
    {
        $activeWarehouse = Warehouse::factory()->create([
            'is_active' => true,
        ]);

        Warehouse::factory()->create([
            'is_active' => false,
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum');

        $this->getJson('/api/v1/warehouses')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeWarehouse->id);
    }

    public function test_can_filter_active_warehouses(): void
    {
        $activeWarehouse = Warehouse::factory()->create([
            'is_active' => true,
        ]);

        Warehouse::factory()->create([
            'is_active' => false,
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum');

        $this->getJson('/api/v1/warehouses?is_active=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeWarehouse->id);
    }

    public function test_can_filter_inactive_warehouses(): void
    {
        $inactiveWarehouse = Warehouse::factory()->create([
            'is_active' => false,
        ]);

        Warehouse::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum');

        $this->getJson('/api/v1/warehouses?is_active=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inactiveWarehouse->id);
    }

    public function test_invalid_is_active_filter_returns_validation_error(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');

        $this->getJson('/api/v1/warehouses?is_active=invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');
    }

    public function test_warehouses_are_sorted_by_name(): void
    {
        $zeta = Warehouse::factory()->create([
            'name'      => 'Zeta',
            'is_active' => true,
        ]);

        $alpha = Warehouse::factory()->create([
            'name'      => 'Alpha',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum');

        $this->getJson('/api/v1/warehouses')
            ->assertOk()
            ->assertJsonPath('data.0.id', $alpha->id)
            ->assertJsonPath('data.1.id', $zeta->id);
    }
}
