<?php

namespace Tests\Feature\Api\V1\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    private function authenticate(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
    }

    public function test_category_can_be_created(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/v1/categories', [
            'name'        => 'Electronics',
            'description' => 'Electronic devices',
            'is_active'   => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Electronics')
            ->assertJsonPath('data.description', 'Electronic devices');

        $this->assertDatabaseHas('categories', [
            'name' => 'Electronics',
        ]);
    }

    public function test_category_can_be_created_with_parent(): void
    {
        $this->authenticate();

        $parent = Category::factory()->create();

        $response = $this->postJson('/api/v1/categories', [
            'parent_id' => $parent->id,
            'name'      => 'Smartphones',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.parent_id', $parent->id);
    }

    public function test_category_creation_requires_name(): void
    {
        $this->authenticate();

        $this->postJson('/api/v1/categories', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_category_cannot_be_created_with_deleted_parent(): void
    {
        $this->authenticate();

        $parent = Category::factory()->create();
        $parent->delete();

        $this->postJson('/api/v1/categories', [
            'parent_id' => $parent->id,
            'name'      => 'Child',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');
    }

    public function test_category_can_be_shown(): void
    {
        $this->authenticate();

        $category = Category::factory()->create([
            'name' => 'Electronics',
        ]);

        $this->getJson("/api/v1/categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.name', 'Electronics');
    }

    public function test_category_can_be_updated(): void
    {
        $this->authenticate();

        $category = Category::factory()->create([
            'name' => 'Old name',
        ]);

        $this->patchJson("/api/v1/categories/{$category->id}", [
            'name'        => 'New name',
            'description' => 'Updated description',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New name')
            ->assertJsonPath('data.description', 'Updated description');

        $this->assertDatabaseHas('categories', [
            'id'   => $category->id,
            'name' => 'New name',
        ]);
    }

    public function test_category_cannot_be_assigned_to_itself_as_parent(): void
    {
        $this->authenticate();

        $category = Category::factory()->create();

        $this->patchJson("/api/v1/categories/{$category->id}", [
            'parent_id' => $category->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');
    }

    public function test_category_cannot_be_moved_into_its_descendant(): void
    {
        $this->authenticate();

        $parent = Category::factory()->create();
        $child = Category::factory()->create([
            'parent_id' => $parent->id,
        ]);

        $this->patchJson("/api/v1/categories/{$parent->id}", [
            'parent_id' => $child->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');
    }

    public function test_category_can_be_soft_deleted(): void
    {
        $this->authenticate();

        $category = Category::factory()->create();

        $this->deleteJson("/api/v1/categories/{$category->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_category_cannot_be_deleted_when_it_has_children(): void
    {
        $this->authenticate();

        $parent = Category::factory()->create();

        Category::factory()->create([
            'parent_id' => $parent->id,
        ]);

        $this->deleteJson("/api/v1/categories/{$parent->id}")
            ->assertConflict();

        $this->assertDatabaseHas('categories', [
            'id'         => $parent->id,
            'deleted_at' => null,
        ]);
    }

    public function test_category_cannot_be_deleted_when_it_has_products(): void
    {
        $this->authenticate();

        $this->seed([
            UnitSeeder::class,
            CurrencySeeder::class,
        ]);

        $category = Category::factory()->create();

        Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $this->deleteJson("/api/v1/categories/{$category->id}")
            ->assertConflict();

        $this->assertDatabaseHas('categories', [
            'id'         => $category->id,
            'deleted_at' => null,
        ]);
    }

    public function test_guest_cannot_create_category(): void
    {
        $this->postJson('/api/v1/categories', [
            'name' => 'Electronics',
        ])->assertUnauthorized();
    }
}
