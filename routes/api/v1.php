<?php

use App\Http\Middleware\EnsureRole;
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
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::middleware('role:admin')->group(function () {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('users/{user}/roles', [UserRoleController::class, 'store'])->name('users.roles.store');
    });
});
