<?php

namespace Tests\Feature\Api\V1\Sales;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_customer_with_all_fields(): void
    {
        $customer = Customer::factory()->create([
            'name'      => 'Acme Corporation',
            'email'     => 'sales@acme.com',
            'phone'     => '+77001234567',
            'address'   => 'Almaty, Kazakhstan',
            'notes'     => 'Important customer',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/v1/customers/{$customer->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $customer->id)
            ->assertJsonPath('data.name', 'Acme Corporation')
            ->assertJsonPath('data.email', 'sales@acme.com')
            ->assertJsonPath('data.phone', '+77001234567')
            ->assertJsonPath('data.address', 'Almaty, Kazakhstan')
            ->assertJsonPath('data.notes', 'Important customer')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'address',
                    'notes',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_authenticated_user_can_view_inactive_customer(): void
    {
        $customer = Customer::factory()->inactive()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/v1/customers/{$customer->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $customer->id)
            ->assertJsonPath('data.is_active', false);
    }

    public function test_guest_cannot_view_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->getJson("/api/v1/customers/{$customer->id}")
            ->assertUnauthorized();
    }

    public function test_nonexistent_customer_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v1/customers/999999')
            ->assertNotFound();
    }
}
