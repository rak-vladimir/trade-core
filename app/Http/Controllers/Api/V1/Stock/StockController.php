<?php

namespace App\Http\Controllers\Api\V1\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Stock\IndexStockRequest;
use App\Http\Resources\Api\V1\Stock\StockResource;
use App\Models\Stock;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexStockRequest $request): AnonymousResourceCollection
    {
        $stocks = Stock::query()
            ->with(['warehouse', 'product.category', 'product.unit', 'product.currency'])
            ->when(
                $request->filled('warehouse_id'),
                fn($query) => $query->where('warehouse_id', $request->integer('warehouse_id')),
            )
            ->when(
                $request->filled('product_id'),
                fn($query) => $query->where('product_id', $request->integer('product_id')),
            )
            ->when(
                $request->filled('search'),
                function ($query) use ($request): void {
                    $search = mb_strtolower($request->string('search')->toString());

                    $query->whereHas('product', function ($productQuery) use ($search): void {
                        $productQuery->where(function ($query) use ($search): void {
                            $query
                                ->where('name', 'ilike', "%{$search}%")
                                ->orWhere('sku', 'ilike', ["%{$search}%"]);
                        });
                    });
                },
            )
            ->orderBy(
                'stocks.' . $request->input('sort', 'created_at'),
                $request->input('direction', 'desc'),
            )
            ->paginate($request->integer('per_page', 15));

        return StockResource::collection($stocks);
    }
}
