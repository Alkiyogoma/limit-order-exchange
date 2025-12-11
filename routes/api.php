<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TradeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']); // Get orderbook
    Route::get('/orders/my', [OrderController::class, 'myOrders']); // Get user's orders
    Route::post('/orders', [OrderController::class, 'store']); // Create order
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']); // Cancel order
    Route::post('/orders/match', [OrderController::class, 'match']); // Manual match trigger

    // Trades
    Route::get('/trades', [TradeController::class, 'index']); // Get user's trade history
});
