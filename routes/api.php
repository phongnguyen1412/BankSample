<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\Admin\Customer\TransactionController as AdminCustomerTransactionController;
use App\Http\Controllers\Api\Customer\LoginController as CustomerLoginController;
use App\Http\Controllers\Api\Customer\RegisterController as CustomerRegisterController;
use App\Http\Controllers\Api\Customer\TransactionController as CustomerTransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/imports', ImportController::class);
        Route::get('/customer-transactions', AdminCustomerTransactionController::class);
    });
});

Route::prefix('customer')->group(function () {
    Route::post('/register', CustomerRegisterController::class);
    Route::post('/login', CustomerLoginController::class);

    Route::middleware('auth:customer_sanctum')->group(function () {
        Route::get('/transaction', CustomerTransactionController::class);
    });
});
