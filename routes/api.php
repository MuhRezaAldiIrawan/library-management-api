<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\BorrowController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Public routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // Protected routes
    Route::middleware('auth:api')->group(function () {

        // Auth routes
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::get('profile', [AuthController::class, 'profile']);
        });

        // Category routes
        Route::apiResource('categories', CategoryController::class);

        // Book routes
        Route::apiResource('books', BookController::class);

        Route::prefix('borrows')->group(function () {
            Route::get('/', [BorrowController::class, 'index']);
            Route::post('/', [BorrowController::class, 'borrow']);
            Route::post('return', [BorrowController::class, 'return']);
            Route::get('{borrow}', [BorrowController::class, 'show']);
        });

    });
});
