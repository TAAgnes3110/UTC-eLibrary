<?php

use App\Http\Controllers\Api\AuthController as BackendAuthController;
use App\Http\Controllers\Frontend\Admin\BookPageController;
use App\Http\Controllers\Frontend\Admin\ChangePasswordController;
use App\Http\Controllers\Frontend\Admin\DashboardController;
use App\Http\Controllers\Frontend\Admin\LibraryCardPageController;
use App\Http\Controllers\Frontend\Admin\LibrarySettingsPageController;
use App\Http\Controllers\Frontend\Admin\LoanPageController;
use App\Http\Controllers\Frontend\Admin\ProfileController;
use App\Http\Controllers\Frontend\Admin\UserController;
use App\Http\Controllers\Frontend\Admin\WarehousePageController;
use App\Http\Controllers\Frontend\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Frontend\Auth\NewPasswordController;
use App\Http\Controllers\Frontend\Auth\PasswordResetLinkController;
use App\Http\Controllers\Frontend\Auth\RegisteredUserController;
use App\Http\Controllers\Frontend\Auth\SocialAuthController;
use App\Enums\RoleType;
use App\Http\Controllers\Frontend\Reader\ReaderPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ReaderPageController::class, 'home'])->name('reader.home');
Route::get('/gioi-thieu', [ReaderPageController::class, 'about'])->name('reader.about');
Route::redirect('/quy-dinh-thu-vien', '/quy-dinh/muon-sach', 301);
Route::prefix('quy-dinh')->group(function () {
    Route::get('/', [ReaderPageController::class, 'regulationsIndex'])->name('reader.regulations.index');
    Route::get('/thu-tuc-lam-the', [ReaderPageController::class, 'regulationsCardProcedure'])->name('reader.regulations.card');
    Route::get('/lich-phuc-vu', [ReaderPageController::class, 'regulationsSchedule'])->name('reader.regulations.schedule');
    Route::get('/muon-sach', [ReaderPageController::class, 'regulationsBorrowing'])->name('reader.regulations.borrowing');
});
Route::get('/tra-cuu-sach', [ReaderPageController::class, 'catalog'])->name('reader.catalog');
Route::get('/tra-cuu-sach/{book}', [ReaderPageController::class, 'catalogShow'])->name('reader.catalog.show');
Route::get('/dich-vu', [ReaderPageController::class, 'services'])->name('reader.services');
Route::prefix('dich-vu')->name('reader.services.')->group(function () {
    Route::get('/cap-the-thu-vien', [ReaderPageController::class, 'servicesLibraryCard'])->name('library-card');
    Route::redirect('/sach-da-luu', '/sach-da-luu', 301)->name('saved-books');
    Route::get('/phieu-muon', [ReaderPageController::class, 'servicesLoanRequests'])->middleware('auth')->name('loan-requests');
    Route::get('/phieu-muon/{loan}', [ReaderPageController::class, 'servicesLoanRequestShow'])->middleware('auth')->name('loan-requests.show');
});

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
    Route::get('/tai-khoan', [ReaderPageController::class, 'profile'])->name('reader.profile');
    Route::get('/tai-khoan/lich-su-yeu-cau-cap-nhat', [ReaderPageController::class, 'profileUpdateRequests'])->name('reader.profile-update-requests');
    Route::get('/tai-khoan/doi-mat-khau', [ReaderPageController::class, 'changePassword'])->name('reader.change-password');
    Route::get('/sach-da-luu', [ReaderPageController::class, 'savedBooks'])->name('reader.saved-books');
    Route::post('/tra-cuu-sach/{book}/luu', [ReaderPageController::class, 'storeSavedBook'])->name('reader.saved-books.store');
    Route::delete('/tra-cuu-sach/{book}/luu', [ReaderPageController::class, 'destroySavedBook'])->name('reader.saved-books.destroy');

    Route::get('/dashboard', function () {
        $user = auth()->user();
        if (! $user) {
            return redirect()->route('login');
        }
        $roleValue = $user->user_type instanceof RoleType
            ? $user->user_type->value
            : ($user->user_type ?? '');
        if ($roleValue !== '' && in_array($roleValue, RoleType::staffRoles(), true)) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('reader.home');
    })->name('dashboard');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('admin')->name('admin.')->middleware('role_or_permission:'.RoleType::SUPER_ADMIN->value.'|role_prefix_'.RoleType::ADMIN->value.'|role_prefix_'.RoleType::LIBRARIAN->value)->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/update-requests', [UserController::class, 'updateRequests'])->name('users.update-requests');
        Route::get('/profile', ProfileController::class)->name('profile');
        Route::get('/change-password', ChangePasswordController::class)->name('change-password');
        Route::get('/books/digital', [BookPageController::class, 'digital'])->name('books.digital');
        Route::get('/books', [BookPageController::class, 'index'])->name('books.index');
        Route::get('/warehouses', [WarehousePageController::class, 'index'])->name('warehouses.index');
        Route::get('/warehouses/bookshelf', [WarehousePageController::class, 'bookshelf'])->name('warehouses.bookshelf');
        Route::get('/library-settings', [LibrarySettingsPageController::class, 'index'])->name('library-settings.index');
        Route::get('/library-cards', [LibraryCardPageController::class, 'index'])->name('library-cards.index');
        Route::get('/library-cards/requests', [LibraryCardPageController::class, 'requests'])->name('library-cards.requests');
        Route::get('/library-cards/counter', [LibraryCardPageController::class, 'counter'])->name('library-cards.counter');
        Route::get('/loans', [LoanPageController::class, 'index'])->name('loans.index');
        Route::get('/loans/renewal-requests', [LoanPageController::class, 'renewalRequests'])->name('loans.renewal-requests');
        Route::get('/loans/create', [LoanPageController::class, 'create'])->name('loans.create');
        Route::get('/loans/{loan}', [LoanPageController::class, 'show'])->name('loans.show');
        Route::get('/loans/{loan}/edit', [LoanPageController::class, 'edit'])->name('loans.edit');
        Route::get('/loans/{loan}/return', [LoanPageController::class, 'returnPage'])->name('loans.return');
    });

});
