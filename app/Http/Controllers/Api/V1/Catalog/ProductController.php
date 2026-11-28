<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Catalog\IndexProductRequest;
use App\Http\Requests\Api\V1\Catalog\StoreProductRequest;
use App\Http\Requests\Api\V1\Catalog\UpdateProductRequest;
use App\Http\Resources\Api\V1\Catalog\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexProductRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $query = Product::query()
            ->with(['category', 'unit', 'currency']);

        if (isset($filters['search']) && $filters['search'] !== '') {
            $search = mb_strtolower($filters['search']);
            $search = "%{$search}%";

            $query->where(function ($query) use ($search): void {
                $query
                    ->where('name', 'ilike', $search)
                    ->orWhere('sku', 'ilike', $search);
            });
        }

        if (isset($filters['category_id'])) {
            $categoryIds = $this->getCategoryTreeIds(
                (int)$filters['category_id'],
            );

            $query->whereIn('category_id', $categoryIds);
        }

        if (isset($filters['unit_id'])) {
            $query->where('unit_id', $filters['unit_id']);
        }

        if (isset($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', $filters['is_active']);
        } else {
            $query->where('is_active', true);
        }

        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 15;

        $products = $query
            ->orderBy($sort, $direction)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::query()->create($request->validated());

        $product->load(['category', 'unit', 'currency']);

        return new ProductResource($product)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): ProductResource
    {
        $product->load(['category', 'unit', 'currency']);

        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        $product->load(['category', 'unit', 'currency']);

        return new ProductResource($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        $hasDependencies = $product->stocks()->exists()
            || $product->stockMovements()->exists()
            || DB::table('order_items')
                ->where('product_id', $product->id)
                ->exists();

        if ($hasDependencies) {
            return response()->json([
                'message' => 'Cannot delete a product that has inventory records, stock movements or order items.',
            ], 409);
        }

        $product->delete();

        return response()->json(null, 204);
    }

    /**
     * Get the selected category and all its descendants.
     *
     * @return array<int>
     */
    private function getCategoryTreeIds(int $categoryId): array
    {
        $childrenByParent = Category::query()
            ->where('is_active', true)
            ->get(['id', 'parent_id'])
            ->groupBy('parent_id');

        $categoryIds = [$categoryId];
        $queue = [$categoryId];

        while ($queue !== []) {
            $parentId = array_shift($queue);

            foreach ($childrenByParent->get($parentId, []) as $child) {
                $categoryIds[] = $child->id;
                $queue[] = $child->id;
            }
        }

        return $categoryIds;
    }
}
