<?php

use App\Enums\RoleType;
use App\Http\Controllers\Api\AuthController as BackendAuthController;
use App\Http\Controllers\Frontend\Admin\DashboardController;
use App\Http\Controllers\Frontend\Admin\ProfileController;
use App\Http\Controllers\Frontend\Admin\UserController;
use App\Http\Controllers\Frontend\Admin\ReaderController;
use App\Http\Controllers\Frontend\Reader\CardController as ReaderCardController;
use App\Http\Controllers\Frontend\Reader\PageController as ReaderPageController;
use App\Http\Controllers\Frontend\Reader\ProfileChangeRequestController as ReaderProfileChangeRequestController;
use App\Http\Controllers\Frontend\Reader\ProfileController as ReaderProfileController;
use Inertia\Inertia;
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
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::get('verify-otp', [RegisteredUserController::class, 'verifyOtpPage'])->name('verify-otp');
    Route::post('verify-otp', [RegisteredUserController::class, 'verifyOtp']);
    Route::post('verify-otp/resend', [RegisteredUserController::class, 'resendOtp'])->name('verify-otp.resend');
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
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
        Route::get('/readers', [ReaderController::class, 'index'])->name('readers.index');
        Route::get('/readers/export', [ReaderController::class, 'export'])->name('readers.export');
        Route::get('/profile', ProfileController::class)->name('profile');
    });

    // Reader-facing "Thư viện số" area
    Route::prefix('library')->name('library.')->group(function () {
        // Dashboard (Tổng quan)
        Route::get('/', function () {
            return Inertia::render('Reader/Dashboard');
        })->name('dashboard');

        // Tra cứu sách - tạm thời là trang Dashboard; cập nhật sau khi có trang riêng
        Route::get('/search', function () {
            return Inertia::render('Reader/Dashboard');
        })->name('search');

        // Sách đã lưu
        Route::get('/saved', [ReaderPageController::class, 'saved'])->name('saved');

        // Xem thẻ / Quản lý thẻ
        Route::get('/card', ReaderCardController::class)->name('card');

        // Yêu cầu chỉnh sửa thông tin
        Route::get('/profile/change-request', [ReaderProfileChangeRequestController::class, 'index'])->name('profile.change-request');

        // Thông tin tài khoản (sử dụng cho các link khác nếu cần)
        Route::get('/profile', [ReaderProfileController::class, 'edit'])->name('profile.edit');

        // Sách mượn
        Route::get('/loans', function () {
            return Inertia::render('Reader/Loans/Index');
        })->name('loans');

        // Giới thiệu & Nội quy (có thể cho phép public sau này)
        Route::get('/intro', [ReaderPageController::class, 'intro'])->name('intro');
        Route::get('/rules', [ReaderPageController::class, 'rules'])->name('rules');
    });
});
