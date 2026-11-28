<?php

namespace Tests\Feature\Api\V1\Catalog;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_get_categories(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_get_categories_as_tree(): void
    {
        $user = User::factory()->create();

        $parent = Category::factory()->create([
            'name'      => 'Electronics',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $child = Category::factory()->create([
            'name'      => 'Computers',
            'parent_id' => $parent->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/categories');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $parent->id)
            ->assertJsonPath('data.0.name', 'Electronics')
            ->assertJsonPath('data.0.children.0.id', $child->id)
            ->assertJsonPath('data.0.children.0.name', 'Computers');
    }

    public function test_it_does_not_return_inactive_categories(): void
    {
        $user = User::factory()->create();

        $activeCategory = Category::factory()->create([
            'name'      => 'Electronics',
            'is_active' => true,
        ]);

        Category::factory()->create([
            'name'      => 'Archived',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/categories');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeCategory->id);
    }

    public function test_it_does_not_return_inactive_children(): void
    {
        $user = User::factory()->create();

        $parent = Category::factory()->create([
            'name'      => 'Electronics',
            'is_active' => true,
        ]);

        Category::factory()->create([
            'name'      => 'Archived',
            'parent_id' => $parent->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/categories');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $parent->id)
            ->assertJsonCount(0, 'data.0.children');
    }

    public function test_it_returns_categories_with_multiple_nesting_levels(): void
    {
        $user = User::factory()->create();

        $root = Category::factory()->create([
            'name'      => 'Electronics',
            'parent_id' => null,
        ]);

        $child = Category::factory()->create([
            'name'      => 'Computers',
            'parent_id' => $root->id,
        ]);

        $grandchild = Category::factory()->create([
            'name'      => 'Laptops',
            'parent_id' => $child->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/categories');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $root->id)
            ->assertJsonPath('data.0.children.0.id', $child->id)
            ->assertJsonPath('data.0.children.0.children.0.id', $grandchild->id);
    }

    public function test_it_returns_categories_sorted_by_name_at_each_level(): void
    {
        $user = User::factory()->create();

        $rootB = Category::factory()->create([
            'name' => 'B Electronics',
        ]);

        $rootA = Category::factory()->create([
            'name' => 'A Office',
        ]);

        Category::factory()->create([
            'name'      => 'Z Computers',
            'parent_id' => $rootA->id,
        ]);

        Category::factory()->create([
            'name'      => 'C Supplies',
            'parent_id' => $rootA->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/categories');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $rootA->id)
            ->assertJsonPath('data.1.id', $rootB->id)
            ->assertJsonPath('data.0.children.0.name', 'C Supplies')
            ->assertJsonPath('data.0.children.1.name', 'Z Computers');
    }
}
