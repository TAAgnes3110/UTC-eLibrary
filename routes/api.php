<?php

use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\Backend\AuthController;
use App\Http\Controllers\Backend\AuthorController;
use App\Http\Controllers\Backend\EmailOTPController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Enums\RoleType;


Route::group(['prefix' => 'auth', 'namespace' => 'App\Http\Controllers\Backend'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('verify-otp', [AuthController::class, 'verifyRegister']);
    Route::post('resend-otp', [EmailOTPController::class, 'store']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

Route::group(['middleware' => ['init']], function () {
    Route::middleware(['role_or_permission:' . RoleType::SUPER_ADMIN->value . '|role_prefix_' . RoleType::ADMIN->value])->group(function () {
        Route::group(['prefix' => '/users'], function () {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store']);
            Route::put('/{user}', [UserController::class, 'update']);
            Route::delete('/{user}', [UserController::class, 'destroy']);
        });

        Route::group(['prefix' => '/authors'], function () {
            Route::get('/', [AuthorController::class, 'index']);
            Route::post('/', [AuthorController::class, 'store']);
            Route::put('/{author}', [AuthorController::class, 'update']);
            Route::delete('/{author}', [AuthorController::class, 'destroy']);
        });
    });
});
