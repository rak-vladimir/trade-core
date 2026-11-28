<?php

namespace App\Services\Inventory;

use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    public function receipt(
        int     $productId,
        int     $warehouseId,
        string  $quantity,
        int     $userId,
        ?string $reference = null,
        ?string $comment = null,
    ): StockMovement
    {
        return \DB::transaction(function () use (
            $productId,
            $warehouseId,
            $quantity,
            $userId,
            $reference,
            $comment,
        ): StockMovement {
            $this->ensureStockExists($productId, $warehouseId);

            $stock = Stock::query()
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->firstOrFail();

            $stock->increment('quantity', $quantity);

            return $this->createMovement(
                productId: $productId,
                fromWarehouseId: null,
                toWarehouseId: $warehouseId,
                type: 'receipt',
                quantity: $quantity,
                userId: $userId,
                reference: $reference,
                comment: $comment,
            );
        });
    }

    public function writeOff(
        int     $productId,
        int     $warehouseId,
        string  $quantity,
        int     $userId,
        ?string $reference = null,
        ?string $comment = null,
    ): StockMovement
    {
        return \DB::transaction(function () use (
            $productId,
            $warehouseId,
            $quantity,
            $userId,
            $reference,
            $comment,
        ): StockMovement {
            $stock = Stock::query()
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                $this->throwInsufficientStock();
            }

            $updated = Stock::query()
                ->whereKey($stock->id)
                ->where('quantity', '>=', $quantity)
                ->decrement('quantity', $quantity);

            if ($updated !== 1) {
                $this->throwInsufficientStock();
            }

            return $this->createMovement(
                productId: $productId,
                fromWarehouseId: $warehouseId,
                toWarehouseId: null,
                type: 'write_off',
                quantity: $quantity,
                userId: $userId,
                reference: $reference,
                comment: $comment,
            );
        });
    }

    public function transfer(
        int     $productId,
        int     $fromWarehouseId,
        int     $toWarehouseId,
        string  $quantity,
        int     $userId,
        ?string $reference = null,
        ?string $comment = null,
    ): StockMovement
    {
        return \DB::transaction(function () use (
            $productId,
            $fromWarehouseId,
            $toWarehouseId,
            $quantity,
            $userId,
            $reference,
            $comment,
        ): StockMovement {
            // Создаём целевой остаток, если его ещё нет.
            $this->ensureStockExists($productId, $toWarehouseId);

            // Блокируем строки в одинаковом порядке для всех переводов.
            $stocks = Stock::query()
                ->where('product_id', $productId)
                ->whereIn('warehouse_id', [
                    $fromWarehouseId,
                    $toWarehouseId,
                ])
                ->orderBy('warehouse_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('warehouse_id');

            $sourceStock = $stocks->get($fromWarehouseId);
            $destinationStock = $stocks->get($toWarehouseId);

            if (!$sourceStock || !$destinationStock) {
                $this->throwInsufficientStock();
            }

            $updated = Stock::query()
                ->whereKey($sourceStock->id)
                ->where('quantity', '>=', $quantity)
                ->decrement('quantity', $quantity);

            if ($updated !== 1) {
                $this->throwInsufficientStock();
            }

            $destinationStock->increment('quantity', $quantity);

            return $this->createMovement(
                productId: $productId,
                fromWarehouseId: $fromWarehouseId,
                toWarehouseId: $toWarehouseId,
                type: 'transfer',
                quantity: $quantity,
                userId: $userId,
                reference: $reference,
                comment: $comment,
            );
        });
    }

    private function ensureStockExists(
        int $productId,
        int $warehouseId,
    ): void
    {
        $now = now();

        \DB::table('stocks')->insertOrIgnore([
            'product_id'   => $productId,
            'warehouse_id' => $warehouseId,
            'quantity'     => 0,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
    }

    private function createMovement(
        int     $productId,
        ?int    $fromWarehouseId,
        ?int    $toWarehouseId,
        string  $type,
        string  $quantity,
        int     $userId,
        ?string $reference,
        ?string $comment,
    ): StockMovement
    {
        return StockMovement::query()->create([
            'product_id'        => $productId,
            'from_warehouse_id' => $fromWarehouseId,
            'to_warehouse_id'   => $toWarehouseId,
            'type'              => $type,
            'quantity'          => $quantity,
            'reference'         => $reference,
            'comment'           => $comment,
            'user_id'           => $userId,
        ]);
    }

    private function throwInsufficientStock(): never
    {
        throw ValidationException::withMessages([
            'quantity' => ['Insufficient stock for this operation.'],
        ]);
    }
}
