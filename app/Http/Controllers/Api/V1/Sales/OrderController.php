<?php

namespace App\Http\Controllers\Api\V1\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sales\IndexOrderRequest;
use App\Http\Requests\Api\V1\Sales\StoreOrderRequest;
use App\Http\Resources\Api\V1\Sales\OrderResource;
use App\Models\Order;
use App\Services\Sales\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function index(IndexOrderRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';

        $orders = Order::query()
            ->with([
                'customer',
                'currency',
                'warehouse',
                'user',
                'items.product.category',
                'items.product.unit',
                'items.product.currency',
            ])
            ->when(
                $filters['search'] ?? null,
                fn($query, $search) => $query->where('number', 'ilike', "%{$search}%")
            )
            ->when(
                $filters['customer_id'] ?? null,
                fn($query, $customerId) => $query->where('customer_id', $customerId)
            )
            ->when(
                $filters['warehouse_id'] ?? null,
                fn($query, $warehouseId) => $query->where('warehouse_id', $warehouseId)
            )
            ->when(
                $filters['status'] ?? null,
                fn($query, $status) => $query->where('status', $status)
            )
            ->when(
                $filters['date_from'] ?? null,
                fn($query, $date) => $query->whereDate('created_at', '>=', $date)
            )
            ->when(
                $filters['date_to'] ?? null,
                fn($query, $date) => $query->whereDate('created_at', '<=', $date)
            )
            ->orderBy($sort, $direction)
            ->when(
                $sort !== 'id',
                fn($query) => $query->orderBy('id', $direction)
            )
            ->paginate($filters['per_page'] ?? 15);

        return OrderResource::collection($orders);
    }

    public function store(
        StoreOrderRequest $request,
        OrderService      $orderService,
    ): JsonResponse
    {
        $order = $orderService->create(
            $request->validated(),
            (int)$request->user()->id,
        );

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Order $order): OrderResource
    {
        return new OrderResource(
            $order->load([
                'customer',
                'currency',
                'warehouse',
                'user',
                'items.product.category',
                'items.product.unit',
                'items.product.currency',
            ])
        );
    }

    public function ship(
        Order        $order,
        OrderService $orderService,
    ): OrderResource
    {
        return new OrderResource(
            $orderService->ship($order)
        );
    }

    public function cancel(
        Order        $order,
        OrderService $orderService,
        Request      $request,
    ): OrderResource
    {
        return new OrderResource(
            $orderService->cancel($order, (int)$request->user()->id)
        );
    }
}
