<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Catalog\StoreCategoryRequest;
use App\Http\Requests\Api\V1\Catalog\UpdateCategoryRequest;
use App\Http\Resources\Api\V1\Catalog\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $categoriesByParent = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->groupBy(fn(Category $category) => $category->parent_id ?? 0);

        $buildTree = function (?int $parentId = null) use (
            &$buildTree,
            $categoriesByParent,
        ): Collection {
            return $categoriesByParent
                ->get($parentId ?? 0, collect())
                ->map(function (Category $category) use (&$buildTree): Category {
                    $category->setRelation(
                        'children',
                        $buildTree($category->id),
                    );

                    return $category;
                });
        };

        return CategoryResource::collection($buildTree());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return new CategoryResource($category)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category): CategoryResource
    {
        $category->load([
            'children' => fn($query) => $query->orderBy('name'),
        ]);

        return new CategoryResource($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());

        return new CategoryResource($category->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): JsonResponse
    {
        if ($category->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a category that has child categories.',
            ], 409);
        }

        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a category that has products.',
            ], 409);
        }

        $category->delete();

        return response()->json(null, 204);
    }
}
