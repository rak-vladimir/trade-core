<?php

namespace Api\V1\Warehouse;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();

        $this->getJson("/api/v1/warehouses/{$warehouse->id}")
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create([
            'code'      => 'WH-100',
            'name'      => 'Main Warehouse',
            'address'   => 'Almaty, Kazakhstan',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/v1/warehouses/{$warehouse->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $warehouse->id)
            ->assertJsonPath('data.code', 'WH-100')
            ->assertJsonPath('data.name', 'Main Warehouse')
            ->assertJsonPath('data.address', 'Almaty, Kazakhstan')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_inactive_warehouse_can_be_viewed(): void
    {
        $warehouse = Warehouse::factory()->create([
            'is_active' => false,
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/v1/warehouses/{$warehouse->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $warehouse->id)
            ->assertJsonPath('data.is_active', false);
    }

    public function test_nonexistent_warehouse_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/warehouses/999999')
            ->assertNotFound();
    }
}
