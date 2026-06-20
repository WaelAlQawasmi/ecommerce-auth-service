<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\UserRoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| Base URL: /api/v1
| All responses use the standard envelope from App\Support\Http\ApiResponse.
|
*/

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
    });
});

Route::middleware('auth:api')->group(function () {
    Route::middleware('role:admin,support')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/search', [UserController::class, 'search'])->name('users.search');
        Route::get('users/count', [UserController::class, 'count'])->name('users.count');
    });

    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::middleware('role:admin')->group(function () {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    });

    Route::middleware(['role:admin,support', 'admin_for_non_customer_role'])->group(function () {
        Route::post('users/{user}/roles', [UserRoleController::class, 'store'])->name('users.roles.store');
    });
});
