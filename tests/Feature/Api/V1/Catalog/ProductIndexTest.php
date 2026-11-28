<?php

namespace Tests\Feature\Api\V1\Catalog;

use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductIndexTest extends TestCase
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

    public function test_guest_cannot_get_products(): void
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_get_products(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }

    public function test_authenticated_user_can_search_products_by_name(): void
    {
        $user = User::factory()->create();

        Product::factory()->create([
            'name' => 'Wireless Keyboard',
            'sku'  => 'TEST-KEYBOARD-001',
        ]);

        Product::factory()->create([
            'name' => 'Office Chair',
            'sku'  => 'TEST-CHAIR-001',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/products?search=Wireless');

        $response->assertOk()
            ->assertJsonFragment([
                'name' => 'Wireless Keyboard',
                'sku'  => 'TEST-KEYBOARD-001',
            ])
            ->assertJsonMissing([
                'name' => 'Office Chair',
                'sku'  => 'TEST-CHAIR-001',
            ]);
    }

    public function test_authenticated_user_can_search_products_by_sku(): void
    {
        $user = User::factory()->create();

        Product::factory()->create([
            'name' => 'Wireless Keyboard',
            'sku'  => 'TEST-KEYBOARD-001',
        ]);

        Product::factory()->create([
            'name' => 'Office Chair',
            'sku'  => 'TEST-CHAIR-001',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/products?search=TEST-CHAIR');

        $response->assertOk()
            ->assertJsonFragment([
                'name' => 'Office Chair',
                'sku'  => 'TEST-CHAIR-001',
            ])
            ->assertJsonMissing([
                'name' => 'Wireless Keyboard',
                'sku'  => 'TEST-KEYBOARD-001',
            ]);
    }

    public function test_authenticated_user_can_filter_products_by_category(): void
    {
        $user = User::factory()->create();

        $category = Category::factory()->create([
            'name' => 'Electronics',
        ]);

        $otherCategory = Category::factory()->create([
            'name' => 'Furniture',
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'name'        => 'Wireless Keyboard',
            'sku'         => 'TEST-KEYBOARD-001',
        ]);

        Product::factory()->create([
            'category_id' => $otherCategory->id,
            'name'        => 'Office Chair',
            'sku'         => 'TEST-CHAIR-001',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/products?category_id={$category->id}");

        $response->assertOk()
            ->assertJsonFragment([
                'sku' => 'TEST-KEYBOARD-001',
            ])
            ->assertJsonMissing([
                'sku' => 'TEST-CHAIR-001',
            ]);
    }

    public function test_authenticated_user_can_filter_products_by_parent_category(): void
    {
        $user = User::factory()->create();

        $parentCategory = Category::factory()->create([
            'name' => 'Electronics',
        ]);

        $childCategory = Category::factory()->create([
            'name'      => 'Computers',
            'parent_id' => $parentCategory->id,
        ]);

        Product::factory()->create([
            'category_id' => $parentCategory->id,
            'name'        => 'Smartphone',
            'sku'         => 'TEST-PHONE-001',
        ]);

        Product::factory()->create([
            'category_id' => $childCategory->id,
            'name'        => 'Laptop',
            'sku'         => 'TEST-LAPTOP-001',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/products?category_id={$parentCategory->id}");

        $response->assertOk()
            ->assertJsonFragment([
                'sku' => 'TEST-PHONE-001',
            ])
            ->assertJsonFragment([
                'sku' => 'TEST-LAPTOP-001',
            ]);
    }

    public function test_authenticated_user_can_filter_products_by_unit(): void
    {
        $user = User::factory()->create();

        $piece = Unit::query()->where('code', 'piece')->firstOrFail();
        $kilogram = Unit::query()->where('code', 'kilogram')->firstOrFail();

        Product::factory()->create([
            'unit_id' => $piece->id,
            'sku'     => 'TEST-PIECE-001',
        ]);

        Product::factory()->create([
            'unit_id' => $kilogram->id,
            'sku'     => 'TEST-KILOGRAM-001',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/products?unit_id={$piece->id}");

        $response->assertOk()
            ->assertJsonFragment(['sku' => 'TEST-PIECE-001'])
            ->assertJsonMissing(['sku' => 'TEST-KILOGRAM-001']);
    }

    public function test_authenticated_user_can_filter_products_by_currency(): void
    {
        $user = User::factory()->create();

        $kzt = Currency::query()->where('code', 'KZT')->firstOrFail();
        $usd = Currency::query()->where('code', 'USD')->firstOrFail();

        Product::factory()->create([
            'currency_id' => $kzt->id,
            'sku'         => 'TEST-KZT-001',
        ]);

        Product::factory()->create([
            'currency_id' => $usd->id,
            'sku'         => 'TEST-USD-001',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/products?currency_id={$kzt->id}");

        $response->assertOk()
            ->assertJsonFragment(['sku' => 'TEST-KZT-001'])
            ->assertJsonMissing(['sku' => 'TEST-USD-001']);
    }

    public function test_authenticated_user_can_filter_products_by_active_status(): void
    {
        $user = User::factory()->create();

        Product::factory()->create([
            'is_active' => true,
            'sku'       => 'TEST-ACTIVE-001',
        ]);

        Product::factory()->create([
            'is_active' => false,
            'sku'       => 'TEST-INACTIVE-001',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/products?is_active=0');

        $response->assertOk()
            ->assertJsonFragment(['sku' => 'TEST-INACTIVE-001'])
            ->assertJsonMissing(['sku' => 'TEST-ACTIVE-001']);
    }

    public function test_authenticated_user_can_sort_products_by_name(): void
    {
        $user = User::factory()->create();

        Product::factory()->create([
            'name' => 'Wireless Mouse',
            'sku'  => 'TEST-MOUSE-001',
        ]);

        Product::factory()->create([
            'name' => 'Keyboard',
            'sku'  => 'TEST-KEYBOARD-001',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/products?sort=name&direction=asc');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Keyboard')
            ->assertJsonPath('data.1.name', 'Wireless Mouse');
    }

    public function test_authenticated_user_can_paginate_products(): void
    {
        $user = User::factory()->create();

        Product::factory()->count(3)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/products?per_page=2&page=1');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_it_rejects_invalid_sort_parameter(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/products?sort=unknown');

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sort');
    }

    public function test_it_rejects_invalid_sort_direction(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/products?direction=random');

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('direction');
    }

    public function test_it_rejects_per_page_greater_than_100(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/products?per_page=101');

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_filtering_by_category_excludes_products_from_inactive_children(): void
    {
        $user = User::factory()->create();

        $parent = Category::factory()->create([
            'is_active' => true,
        ]);

        $inactiveChild = Category::factory()->create([
            'parent_id' => $parent->id,
            'is_active' => false,
        ]);

        $product = Product::factory()->create([
            'category_id' => $inactiveChild->id,
            'is_active'   => true,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/products?category_id={$parent->id}");

        $response
            ->assertOk()
            ->assertJsonMissing([
                'id'  => $product->id,
                'sku' => $product->sku,
            ]);
    }

    public function test_it_rejects_inactive_category_filter(): void
    {
        $user = User::factory()->create();

        $category = Category::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/products?category_id={$category->id}");

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    }
}
