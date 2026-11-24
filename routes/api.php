<?php

use App\Http\Controllers\Api\V1\Auth\AuthenticatedSessionController;
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
    });
});
