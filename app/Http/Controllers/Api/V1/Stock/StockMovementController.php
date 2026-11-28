<?php

namespace App\Http\Controllers\Api\V1\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Stock\IndexStockMovementRequest;
use App\Http\Requests\Api\V1\Stock\StoreStockReceiptRequest;
use App\Http\Requests\Api\V1\Stock\StoreStockTransferRequest;
use App\Http\Requests\Api\V1\Stock\StoreStockWriteOffRequest;
use App\Http\Resources\Api\V1\Stock\StockMovementResource;
use App\Models\StockMovement;
use App\Services\Inventory\StockMovementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockMovementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexStockMovementRequest $request): AnonymousResourceCollection
    {
        $movements = StockMovement::query()
            ->with([
                'product.category',
                'product.unit',
                'product.currency',
                'fromWarehouse',
                'toWarehouse',
                'user',
            ])
            ->when(
                $request->filled('type'),
                fn(Builder $query) => $query->where('type', $request->input('type')),
            )
            ->when(
                $request->filled('product_id'),
                fn(Builder $query) => $query->where('product_id', $request->integer('product_id')),
            )
            ->when(
                $request->filled('warehouse_id'),
                fn(Builder $query) => $query->where(function (Builder $query) use ($request): void {
                    $warehouseId = $request->integer('warehouse_id');

                    $query
                        ->where('from_warehouse_id', $warehouseId)
                        ->orWhere('to_warehouse_id', $warehouseId);
                }),
            )
            ->when(
                $request->filled('date_from'),
                fn(Builder $query) => $query->whereDate('created_at', '>=', $request->input('date_from')),
            )
            ->when(
                $request->filled('date_to'),
                fn(Builder $query) => $query->whereDate('created_at', '<=', $request->input('date_to')),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return StockMovementResource::collection($movements);
    }

    public function receipt(
        StoreStockReceiptRequest $request,
        StockMovementService     $service,
    ): StockMovementResource
    {
        $data = $request->validated();

        $movement = $service->receipt(
            productId: $data['product_id'],
            warehouseId: $data['to_warehouse_id'],
            quantity: $data['quantity'],
            userId: $request->user()->id,
            reference: $data['reference'] ?? null,
            comment: $data['comment'] ?? null,
        );

        return new StockMovementResource(
            $movement->load([
                'product.category',
                'product.unit',
                'product.currency',
                'fromWarehouse',
                'toWarehouse',
                'user',
            ]),
        );
    }

    public function writeOff(
        StoreStockWriteOffRequest $request,
        StockMovementService      $service,
    ): StockMovementResource
    {
        $data = $request->validated();

        $movement = $service->writeOff(
            productId: $data['product_id'],
            warehouseId: $data['from_warehouse_id'],
            quantity: $data['quantity'],
            userId: $request->user()->id,
            reference: $data['reference'] ?? null,
            comment: $data['comment'] ?? null,
        );

        return new StockMovementResource(
            $movement->load([
                'product.category',
                'product.unit',
                'product.currency',
                'fromWarehouse',
                'toWarehouse',
                'user',
            ]),
        );
    }

    public function transfer(
        StoreStockTransferRequest $request,
        StockMovementService      $service,
    ): StockMovementResource
    {
        $data = $request->validated();

        $movement = $service->transfer(
            productId: $data['product_id'],
            fromWarehouseId: $data['from_warehouse_id'],
            toWarehouseId: $data['to_warehouse_id'],
            quantity: $data['quantity'],
            userId: $request->user()->id,
            reference: $data['reference'] ?? null,
            comment: $data['comment'] ?? null,
        );

        return new StockMovementResource(
            $movement->load([
                'product.category',
                'product.unit',
                'product.currency',
                'fromWarehouse',
                'toWarehouse',
                'user',
            ]),
        );
    }
}
