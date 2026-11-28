<?php

namespace App\Services\Sales;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function create(array $data, int $userId): Order
    {
        return DB::transaction(function () use ($data, $userId) {
            $customer = Customer::query()
                ->whereKey($data['customer_id'])
                ->lockForUpdate()
                ->first();

            if (!$customer || !$customer->is_active) {
                throw ValidationException::withMessages([
                    'customer_id' => 'The selected customer is unavailable.',
                ]);
            }

            $warehouse = Warehouse::query()
                ->whereKey($data['warehouse_id'])
                ->lockForUpdate()
                ->first();

            if (!$warehouse || !$warehouse->is_active) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'The selected warehouse is unavailable.',
                ]);
            }

            $productIds = collect($data['items'])
                ->pluck('product_id')
                ->map(fn($id) => (int)$id)
                ->all();

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($productIds)) {
                throw ValidationException::withMessages([
                    'items' => 'Some products are unavailable.',
                ]);
            }

            foreach ($products as $product) {
                if (!$product->is_active || $product->trashed()) {
                    throw ValidationException::withMessages([
                        'items' => "Product ID {$product->id} is unavailable.",
                    ]);
                }
            }

            $currencyIds = $products->pluck('currency_id')->unique();

            if ($currencyIds->count() !== 1) {
                throw ValidationException::withMessages([
                    'items' => 'All products in an order must use the same currency.',
                ]);
            }

            $stocks = Stock::query()
                ->where('warehouse_id', $warehouse->id)
                ->whereIn('product_id', $productIds)
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            foreach ($data['items'] as $item) {
                $productId = (int)$item['product_id'];
                $stock = $stocks->get($productId);

                if (
                    !$stock ||
                    bccomp($stock->quantity, (string)$item['quantity'], 4) < 0
                ) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for product ID {$productId}.",
                    ]);
                }
            }

            // DECIMAL(20, 4): maximum 16 integer digits and 4 decimal places.
            $maxAmount = '9999999999999999.9999';
            $orderTotal = '0.0000';
            $orderItems = [];

            // Calculate and validate all amounts before modifying inventory.
            foreach ($data['items'] as $item) {
                $productId = (int)$item['product_id'];
                $product = $products->get($productId);
                $quantity = (string)$item['quantity'];

                $totalPrice = DB::selectOne(
                    'SELECT ROUND(?::numeric * ?::numeric, 4) AS total_price',
                    [$quantity, $product->price]
                )->total_price;

                if (bccomp((string)$totalPrice, $maxAmount, 4) > 0) {
                    throw ValidationException::withMessages([
                        'items' => "The total price for product ID {$productId} exceeds the supported amount.",
                    ]);
                }

                $orderTotal = bcadd($orderTotal, (string)$totalPrice, 4);

                if (bccomp($orderTotal, $maxAmount, 4) > 0) {
                    throw ValidationException::withMessages([
                        'items' => 'The order total exceeds the supported amount.',
                    ]);
                }

                $orderItems[] = [
                    'product_id'  => $productId,
                    'product'     => $product,
                    'quantity'    => $quantity,
                    'total_price' => $totalPrice,
                ];
            }

            $order = Order::query()->create([
                'number'       => 'ORD-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                'customer_id'  => $customer->id,
                'currency_id'  => $currencyIds->first(),
                'warehouse_id' => $warehouse->id,
                'user_id'      => $userId,
                'status'       => OrderStatus::Confirmed,
                'total_amount' => $orderTotal,
                'comment'      => $data['comment'] ?? null,
            ]);

            foreach ($orderItems as $item) {
                $productId = $item['product_id'];
                $product = $item['product'];
                $quantity = $item['quantity'];

                $stocks->get($productId)->decrement('quantity', $quantity);

                $order->items()->create([
                    'product_id'  => $productId,
                    'quantity'    => $quantity,
                    'unit_price'  => $product->price,
                    'total_price' => $item['total_price'],
                ]);

                StockMovement::query()->create([
                    'product_id'        => $productId,
                    'from_warehouse_id' => $warehouse->id,
                    'to_warehouse_id'   => null,
                    'type'              => 'write_off',
                    'quantity'          => $quantity,
                    'reference'         => $order->number,
                    'comment'           => 'Order ' . $order->number,
                    'user_id'           => $userId,
                ]);
            }

            return $this->loadOrderRelations($order);
        });
    }

    public function ship(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if (!$order->status->canBeShipped()) {
                throw ValidationException::withMessages([
                    'status' => 'This order cannot be shipped.',
                ]);
            }

            $order->update([
                'status' => OrderStatus::Shipped,
            ]);

            return $this->loadOrderRelations($order);
        });
    }

    public function cancel(Order $order, int $userId): Order
    {
        return DB::transaction(function () use ($order, $userId) {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if (!$order->status->canBeCancelled()) {
                throw ValidationException::withMessages([
                    'status' => 'This order cannot be cancelled.',
                ]);
            }

            if ($order->status === OrderStatus::Confirmed) {
                $items = $order->items()
                    ->orderBy('product_id')
                    ->get();

                $stocks = Stock::query()
                    ->where('warehouse_id', $order->warehouse_id)
                    ->whereIn('product_id', $items->pluck('product_id'))
                    ->orderBy('product_id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('product_id');

                foreach ($items as $item) {
                    $stock = $stocks->get($item->product_id);

                    if (!$stock) {
                        throw new \LogicException(
                            "Stock record not found for product {$item->product_id}."
                        );
                    }

                    $stock->increment('quantity', $item->quantity);

                    StockMovement::query()->create([
                        'product_id'        => $item->product_id,
                        'from_warehouse_id' => null,
                        'to_warehouse_id'   => $order->warehouse_id,
                        'type'              => 'receipt',
                        'quantity'          => $item->quantity,
                        'reference'         => $order->number,
                        'comment'           => "Cancellation of order {$order->number}",
                        'user_id'           => $userId,
                    ]);
                }
            }

            $order->update([
                'status' => OrderStatus::Cancelled,
            ]);

            return $this->loadOrderRelations($order);
        });
    }

    private function loadOrderRelations(Order $order): Order
    {
        return $order->load([
            'customer',
            'currency',
            'warehouse',
            'user',
            'items.product.category',
            'items.product.unit',
            'items.product.currency',
        ]);
    }
}
