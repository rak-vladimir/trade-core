<?php

namespace Tests\Feature\Api\V1\Sales;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(), 'sanctum');
    }

    public function test_authenticated_user_can_list_customers(): void
    {
        Customer::factory()->count(3)->create();

        $this->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_customers_can_be_searched_by_name(): void
    {
        Customer::factory()->create(['name' => 'Acme Corporation']);
        Customer::factory()->create(['name' => 'Other Company']);

        $this->getJson('/api/v1/customers?search=Acme')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Acme Corporation');
    }

    public function test_customers_can_be_searched_by_email_case_insensitively(): void
    {
        Customer::factory()->create([
            'name'  => 'Acme',
            'email' => 'sales@acme.com',
        ]);

        Customer::factory()->create([
            'name'  => 'Other',
            'email' => 'info@example.com',
        ]);

        $this->getJson('/api/v1/customers?search=SALES@ACME')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'sales@acme.com');
    }

    public function test_customers_can_be_searched_by_phone(): void
    {
        Customer::factory()->create([
            'name'  => 'Acme',
            'phone' => '+77001234567',
        ]);

        Customer::factory()->create([
            'name'  => 'Other',
            'phone' => '+77007654321',
        ]);

        $this->getJson('/api/v1/customers?search=1234567')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.phone', '+77001234567');
    }

    public function test_customers_can_be_filtered_by_activity(): void
    {
        Customer::factory()->create(['is_active' => true]);
        Customer::factory()->inactive()->create();

        $this->getJson('/api/v1/customers?is_active=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_active', false);
    }

    public function test_customers_can_be_sorted_by_name_ascending(): void
    {
        Customer::factory()->create(['name' => 'Charlie']);
        Customer::factory()->create(['name' => 'Alice']);
        Customer::factory()->create(['name' => 'Bob']);

        $this->getJson('/api/v1/customers?sort=name&direction=asc')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Alice')
            ->assertJsonPath('data.1.name', 'Bob')
            ->assertJsonPath('data.2.name', 'Charlie');
    }

    public function test_customers_can_be_paginated(): void
    {
        Customer::factory()->count(3)->create();

        $this->getJson('/api/v1/customers?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 2);

        $this->getJson('/api/v1/customers?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_invalid_index_filters_are_rejected(): void
    {
        $invalidQueries = [
            '?sort=email',
            '?direction=random',
            '?per_page=101',
            '?per_page=0',
            '?is_active=invalid',
        ];

        foreach ($invalidQueries as $query) {
            $this->getJson('/api/v1/customers' . $query)
                ->assertUnprocessable();
        }
    }

    public function test_guest_cannot_list_customers(): void
    {
        auth('sanctum')->forgetUser();

        $this->getJson('/api/v1/customers')
            ->assertUnauthorized();
    }
}
