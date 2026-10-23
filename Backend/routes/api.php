<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OperationalCostController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Public catalog endpoints.
Route::get('/products', [ProductController::class, 'index'])->middleware('throttle:60,1');
Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:30,1');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'role:admin,cashier'])->group(function (): void {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/pay', [OrderController::class, 'pay']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss']);

    Route::get('/operational-costs', [OperationalCostController::class, 'index']);
    Route::post('/operational-costs', [OperationalCostController::class, 'store']);
});
