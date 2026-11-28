<?php

namespace Tests\Feature\Api\V1\Catalog;

use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductCrudTest extends TestCase
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

    private function authenticate(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
    }

    private function productData(array $overrides = []): array
    {
        return array_merge([
            'category_id' => Category::factory()->create()->id,
            'unit_id'     => Unit::query()->where('is_active', true)->firstOrFail()->id,
            'currency_id' => Currency::query()->where('is_active', true)->firstOrFail()->id,
            'sku'         => 'TEST-' . fake()->unique()->numerify('######'),
            'name'        => 'Test product',
            'description' => 'Test description',
            'price'       => '125.5000',
            'is_active'   => true,
        ], $overrides);
    }

    public function test_product_can_be_created(): void
    {
        $this->authenticate();

        $data = $this->productData();

        $this->postJson('/api/v1/products', $data)
            ->assertCreated()
            ->assertJsonPath('data.sku', $data['sku'])
            ->assertJsonPath('data.name', $data['name'])
            ->assertJsonPath('data.price', '125.5000')
            ->assertJsonPath('data.category.id', $data['category_id'])
            ->assertJsonPath('data.unit.id', $data['unit_id'])
            ->assertJsonPath('data.currency.id', $data['currency_id']);

        $this->assertDatabaseHas('products', [
            'sku'  => $data['sku'],
            'name' => $data['name'],
        ]);
    }

    public function test_product_creation_requires_required_fields(): void
    {
        $this->authenticate();

        $this->postJson('/api/v1/products', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'category_id',
                'unit_id',
                'currency_id',
                'sku',
                'name',
                'price',
            ]);
    }

    public function test_product_sku_must_be_unique(): void
    {
        $this->authenticate();

        $product = Product::factory()->create();

        $this->postJson('/api/v1/products', $this->productData([
            'sku' => $product->sku,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sku');
    }

    public function test_product_cannot_use_inactive_category(): void
    {
        $this->authenticate();

        $category = Category::factory()->create([
            'is_active' => false,
        ]);

        $this->postJson('/api/v1/products', $this->productData([
            'category_id' => $category->id,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    }

    public function test_product_price_must_fit_decimal_precision(): void
    {
        $this->authenticate();

        $this->postJson('/api/v1/products', $this->productData([
            'price' => '10000000000000000.0000',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('price');
    }

    public function test_product_can_be_shown(): void
    {
        $this->authenticate();

        $product = Product::factory()->create();

        $this->getJson("/api/v1/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.sku', $product->sku)
            ->assertJsonStructure([
                'data' => [
                    'category',
                    'unit',
                    'currency',
                ],
            ]);
    }

    public function test_product_can_be_updated(): void
    {
        $this->authenticate();

        $product = Product::factory()->create();

        $this->patchJson("/api/v1/products/{$product->id}", [
            'name'        => 'Updated product',
            'price'       => '250.7500',
            'description' => 'Updated description',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated product')
            ->assertJsonPath('data.price', '250.7500')
            ->assertJsonPath('data.description', 'Updated description');

        $this->assertDatabaseHas('products', [
            'id'    => $product->id,
            'name'  => 'Updated product',
            'price' => '250.7500',
        ]);
    }

    public function test_product_can_keep_its_own_sku_when_updated(): void
    {
        $this->authenticate();

        $product = Product::factory()->create();

        $this->patchJson("/api/v1/products/{$product->id}", [
            'sku' => $product->sku,
        ])->assertOk();
    }

    public function test_product_can_be_soft_deleted(): void
    {
        $this->authenticate();

        $product = Product::factory()->create();

        $this->deleteJson("/api/v1/products/{$product->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    }

    public function test_product_cannot_be_deleted_when_it_has_stock_records(): void
    {
        $this->authenticate();

        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        DB::table('stocks')->insert([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => '0.0000',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->deleteJson("/api/v1/products/{$product->id}")
            ->assertConflict();

        $this->assertDatabaseHas('products', [
            'id'         => $product->id,
            'deleted_at' => null,
        ]);
    }

    public function test_guest_cannot_create_product(): void
    {
        $this->postJson('/api/v1/products', [])
            ->assertUnauthorized();
    }

    public function test_guest_cannot_delete_product(): void
    {
        $product = Product::factory()->create();

        $this->deleteJson("/api/v1/products/{$product->id}")
            ->assertUnauthorized();
    }
}
