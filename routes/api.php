<?php

use App\Http\Controllers\Backend\AuthController;
use App\Http\Controllers\Backend\EmailOTPController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'auth', 'namespace' => 'App\Http\Controllers\Backend'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('verify-otp', [AuthController::class, 'verifyRegister']);
    Route::post('resend-otp', [EmailOTPController::class, 'store']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
