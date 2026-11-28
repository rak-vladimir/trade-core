<?php

use App\Http\Controllers\Api\V1\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\V1\Catalog\CategoryController;
use App\Http\Controllers\Api\V1\Catalog\ProductController;
use App\Http\Controllers\Api\V1\Sales\CustomerController;
use App\Http\Controllers\Api\V1\Sales\OrderController;
use App\Http\Controllers\Api\V1\Stock\ProductStockBalanceController;
use App\Http\Controllers\Api\V1\Stock\StockController;
use App\Http\Controllers\Api\V1\Stock\StockMovementController;
use App\Http\Controllers\Api\V1\Warehouse\WarehouseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
        ]);
    });

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

        Route::apiResource('categories', CategoryController::class);

        Route::apiResource('products', ProductController::class);

        Route::apiResource('warehouses', WarehouseController::class);

        Route::get('/stocks', [StockController::class, 'index']);

        Route::get('/stock-movements', [StockMovementController::class, 'index']);

        Route::post('/stock-movements/receipts', [StockMovementController::class, 'receipt']);

        Route::post('/stock-movements/write-offs', [StockMovementController::class, 'writeOff']);

        Route::post('/stock-movements/transfers', [StockMovementController::class, 'transfer']);

        Route::get('/products/{product}/stock-balance', ProductStockBalanceController::class)
            ->name('products.stock-balance');

        Route::get('/customers', [CustomerController::class, 'index'])
            ->name('customers.index');

        Route::get('/customers/{customer}', [CustomerController::class, 'show'])
            ->name('customers.show');

        Route::get('/orders', [OrderController::class, 'index'])
            ->name('orders.index');

        Route::post('/orders', [OrderController::class, 'store']);

        Route::get('/orders/{order}', [OrderController::class, 'show'])
            ->name('orders.show');

        Route::post('/orders/{order}/ship', [OrderController::class, 'ship'])
            ->name('orders.ship');

        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])
            ->name('orders.cancel');
    });
});
