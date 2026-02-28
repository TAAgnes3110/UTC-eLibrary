<?php

use App\Enums\RoleType;
use App\Http\Controllers\Backend\AuthController as BackendAuthController;
use App\Http\Controllers\Frontend\Admin\AuthorController;
use App\Http\Controllers\Frontend\Admin\BookController;
use App\Http\Controllers\Frontend\Admin\CardController;
use App\Http\Controllers\Frontend\Admin\CategoryController;
use App\Http\Controllers\Frontend\Admin\DashboardController;
use App\Http\Controllers\Frontend\Admin\LibraryController;
use App\Http\Controllers\Frontend\Admin\LoanController;
use App\Http\Controllers\Frontend\Admin\ProfileController;
use App\Http\Controllers\Frontend\Admin\PublisherController;
use App\Http\Controllers\Frontend\Admin\ReaderController;
use App\Http\Controllers\Frontend\Admin\SearchController;
use App\Http\Controllers\Frontend\Admin\SettingsController;
use App\Http\Controllers\Frontend\Admin\StatsController;
use App\Http\Controllers\Frontend\Admin\UserController;
use App\Http\Controllers\Frontend\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Frontend\Auth\NewPasswordController;
use App\Http\Controllers\Frontend\Auth\PasswordResetLinkController;
use App\Http\Controllers\Frontend\Auth\RegisteredUserController;
use App\Http\Controllers\Frontend\Auth\SocialAuthController;
use App\Http\Controllers\Frontend\Reader\BookController as ReaderBookController;
use App\Http\Controllers\Frontend\Reader\CardController as ReaderCardController;
use App\Http\Controllers\Frontend\Reader\DashboardController as ReaderDashboardController;
use App\Http\Controllers\Frontend\Reader\LoanController as ReaderLoanController;
use App\Http\Controllers\Frontend\Reader\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

// Auth (guest)
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

// Reader (library) – public + auth
Route::prefix('library')->name('library.')->group(function () {
    Route::get('/', [ReaderBookController::class, 'search'])->name('search');
    Route::get('/books/{book}', [ReaderBookController::class, 'show'])->name('books.show');
    Route::get('/saved', [PageController::class, 'saved'])->name('saved')->middleware('auth');
    Route::get('/intro', [PageController::class, 'intro'])->name('intro');
    Route::get('/rules', [PageController::class, 'rules'])->name('rules');
    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', ReaderDashboardController::class)->name('dashboard');
        Route::get('/card', ReaderCardController::class)->name('card');
        Route::get('/loans', ReaderLoanController::class)->name('loans');
    });
});

// Auth required: dashboard redirect + logout + admin
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = request()->user();
        $roleValue = $user->user_type instanceof RoleType ? $user->user_type->value : ($user->user_type ?? null);
        $isStaff = $roleValue && in_array($roleValue, RoleType::staffRoles(), true);
        return redirect()->route($isStaff ? 'admin.dashboard' : 'library.dashboard');
    })->name('dashboard');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/search', [SearchController::class, 'index'])->name('search');
        Route::get('/books', [BookController::class, 'index'])->name('books.index');
        Route::get('/books/trash', [BookController::class, 'trash'])->name('books.trash');
        Route::post('/books/restore/{id}', [BookController::class, 'restore'])->name('books.restore');
        Route::delete('/books/force/{id}', [BookController::class, 'forceDelete'])->name('books.force');
        Route::get('/books/export', [BookController::class, 'export'])->name('books.export');
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/authors', [AuthorController::class, 'index'])->name('authors.index');
        Route::get('/authors/trash', [AuthorController::class, 'trash'])->name('authors.trash');
        Route::post('/authors/restore/{id}', [AuthorController::class, 'restore'])->name('authors.restore');
        Route::delete('/authors/force/{id}', [AuthorController::class, 'forceDelete'])->name('authors.force');
        Route::get('/publishers', [PublisherController::class, 'index'])->name('publishers.index');
        Route::get('/readers', [ReaderController::class, 'index'])->name('readers.index');
        Route::get('/readers/export', [ReaderController::class, 'export'])->name('readers.export');
        Route::get('/cards', [CardController::class, 'index'])->name('cards.index');
        Route::prefix('library')->name('library.')->group(function () {
            Route::get('/slips', [LibraryController::class, 'slips'])->name('slips');
            Route::get('/liquidation', [LibraryController::class, 'liquidation'])->name('liquidation');
            Route::get('/inventory', [LibraryController::class, 'inventory'])->name('inventory');
        });
        Route::prefix('loans')->name('loans.')->group(function () {
            Route::get('/', [LoanController::class, 'index'])->name('index');
            Route::get('/extensions', [LoanController::class, 'extensions'])->name('extensions');
            Route::get('/onsite', [LoanController::class, 'onsite'])->name('onsite');
            Route::get('/penalties', [LoanController::class, 'penalties'])->name('penalties');
        });
        Route::get('/stats', [StatsController::class, 'index'])->name('stats.index');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/trash', [UserController::class, 'trash'])->name('users.trash');
        Route::post('/users/restore/{id}', [UserController::class, 'restore'])->name('users.restore');
        Route::delete('/users/force/{id}', [UserController::class, 'forceDelete'])->name('users.force');
        Route::post('/users/toggle-status/{id}', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('/users/{id}/avatar', [UserController::class, 'updateAvatar'])->name('users.update-avatar');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::get('/profile', ProfileController::class)->name('profile');
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::get('/rules', [SettingsController::class, 'rules'])->name('rules');
            Route::get('/content', [SettingsController::class, 'content'])->name('content');
            Route::get('/appearance', [SettingsController::class, 'appearance'])->name('appearance');
        });
    });
});
