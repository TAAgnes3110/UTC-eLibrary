<?php

use App\Http\Controllers\Api\AuthController as BackendAuthController;
use App\Http\Controllers\Frontend\Admin\BookPageController;
use App\Http\Controllers\Frontend\Admin\DashboardController;
use App\Http\Controllers\Frontend\Admin\LibraryCardPageController;
use App\Http\Controllers\Frontend\Admin\ProfileController;
use App\Http\Controllers\Frontend\Admin\UserController;
use App\Http\Controllers\Frontend\Admin\WarehousePageController;
use App\Http\Controllers\Frontend\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Frontend\Auth\NewPasswordController;
use App\Http\Controllers\Frontend\Auth\PasswordResetLinkController;
use App\Http\Controllers\Frontend\Auth\RegisteredUserController;
use App\Http\Controllers\Frontend\Auth\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [BackendAuthController::class, 'login']);
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::get('verify-otp', [RegisteredUserController::class, 'verifyOtpPage'])->name('verify-otp');
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::get('reset-password', [NewPasswordController::class, 'create'])->name('password.reset');
});
Route::get('/auth/microsoft', [SocialAuthController::class, 'redirectToMicrosoft'])->name('auth.microsoft');
Route::get('/auth/microsoft/callback', [SocialAuthController::class, 'handleMicrosoftCallback']);

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('admin.dashboard');
    })->name('dashboard');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/profile', ProfileController::class)->name('profile');
        Route::get('/books/digital', [BookPageController::class, 'digital'])->name('books.digital');
        Route::get('/books', [BookPageController::class, 'index'])->name('books.index');
        Route::get('/warehouses', [WarehousePageController::class, 'index'])->name('warehouses.index');
        Route::get('/library-cards', [LibraryCardPageController::class, 'index'])->name('library-cards.index');
        Route::get('/library-cards/requests', [LibraryCardPageController::class, 'requests'])->name('library-cards.requests');
        Route::get('/library-cards/counter', [LibraryCardPageController::class, 'counter'])->name('library-cards.counter');
    });

});
