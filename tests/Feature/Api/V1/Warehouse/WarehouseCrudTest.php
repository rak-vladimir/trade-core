<?php

namespace Tests\Feature\Api\V1\Warehouse;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WarehouseCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            CurrencySeeder::class,
            UnitSeeder::class,
        ]);
    }

    public function test_warehouse_can_be_created(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/v1/warehouses', [
            'code'    => 'WH-001',
            'name'    => 'Main Warehouse',
            'address' => 'Main street, 1',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.code', 'WH-001')
            ->assertJsonPath('data.name', 'Main Warehouse')
            ->assertJsonPath('data.address', 'Main street, 1')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('warehouses', [
            'code'      => 'WH-001',
            'name'      => 'Main Warehouse',
            'is_active' => true,
        ]);
    }

    public function test_warehouse_creation_requires_code_and_name(): void
    {
        $this->authenticate();

        $this->postJson('/api/v1/warehouses', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code', 'name']);
    }

    public function test_warehouse_code_must_be_unique(): void
    {
        $this->authenticate();

        Warehouse::factory()->create([
            'code' => 'WH-001',
        ]);

        $this->postJson('/api/v1/warehouses', [
            'code' => 'WH-001',
            'name' => 'Another Warehouse',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    public function test_warehouse_can_be_shown(): void
    {
        $this->authenticate();

        $warehouse = Warehouse::factory()->create();

        $this->getJson("/api/v1/warehouses/{$warehouse->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $warehouse->id)
            ->assertJsonPath('data.code', $warehouse->code)
            ->assertJsonPath('data.name', $warehouse->name);
    }

    public function test_warehouse_can_be_updated(): void
    {
        $this->authenticate();

        $warehouse = Warehouse::factory()->create([
            'code' => 'WH-001',
            'name' => 'Old Name',
        ]);

        $this->putJson("/api/v1/warehouses/{$warehouse->id}", [
            'name'      => 'Updated Name',
            'address'   => 'New address',
            'is_active' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.address', 'New address')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('warehouses', [
            'id'        => $warehouse->id,
            'code'      => 'WH-001',
            'name'      => 'Updated Name',
            'is_active' => false,
        ]);
    }

    public function test_warehouse_can_keep_its_own_code_when_updated(): void
    {
        $this->authenticate();

        $warehouse = Warehouse::factory()->create([
            'code' => 'WH-001',
        ]);

        $this->putJson("/api/v1/warehouses/{$warehouse->id}", [
            'code' => 'WH-001',
        ])
            ->assertOk()
            ->assertJsonPath('data.code', 'WH-001');
    }

    public function test_warehouse_code_must_be_unique_when_updated(): void
    {
        $this->authenticate();

        $first = Warehouse::factory()->create([
            'code' => 'WH-001',
        ]);

        $second = Warehouse::factory()->create([
            'code' => 'WH-002',
        ]);

        $this->putJson("/api/v1/warehouses/{$second->id}", [
            'code' => $first->code,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    public function test_warehouse_can_be_deleted_without_dependencies(): void
    {
        $this->authenticate();

        $warehouse = Warehouse::factory()->create();

        $this->deleteJson("/api/v1/warehouses/{$warehouse->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('warehouses', [
            'id' => $warehouse->id,
        ]);
    }

    public function test_warehouse_cannot_be_deleted_when_it_has_stock_records(): void
    {
        $this->authenticate();

        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        DB::table('stocks')->insert([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 0,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->deleteJson("/api/v1/warehouses/{$warehouse->id}")
            ->assertConflict();

        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
        ]);
    }

    public function test_guest_cannot_manage_warehouses(): void
    {
        auth('sanctum')->forgetUser();

        $warehouse = Warehouse::factory()->create();

        $this->postJson('/api/v1/warehouses', [
            'code' => 'WH-001',
            'name' => 'Main Warehouse',
        ])->assertUnauthorized();

        $this->getJson("/api/v1/warehouses/{$warehouse->id}")
            ->assertUnauthorized();
    }
}
