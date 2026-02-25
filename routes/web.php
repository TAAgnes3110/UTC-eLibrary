<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
  Route::get('login', [\App\Http\Controllers\Frontend\Auth\AuthenticatedSessionController::class, 'create'])
    ->name('login');
  Route::post('login', [\App\Http\Controllers\Backend\AuthController::class, 'login']);

  Route::get('register', [\App\Http\Controllers\Frontend\Auth\RegisteredUserController::class, 'create'])->name('register');
  Route::post('register', [\App\Http\Controllers\Frontend\Auth\RegisteredUserController::class, 'store']);

  Route::get('verify-otp', [\App\Http\Controllers\Frontend\Auth\RegisteredUserController::class, 'verifyOtpPage'])->name('verify-otp');
  Route::post('verify-otp', [\App\Http\Controllers\Frontend\Auth\RegisteredUserController::class, 'verifyOtp']);
  Route::post('verify-otp/resend', [\App\Http\Controllers\Frontend\Auth\RegisteredUserController::class, 'resendOtp'])->name('verify-otp.resend');

  Route::get('forgot-password', [\App\Http\Controllers\Frontend\Auth\PasswordResetLinkController::class, 'create'])->name('password.request');
  Route::post('forgot-password', [\App\Http\Controllers\Frontend\Auth\PasswordResetLinkController::class, 'store'])->name('password.email');

  Route::get('reset-password', [\App\Http\Controllers\Frontend\Auth\NewPasswordController::class, 'create'])->name('password.reset');
  Route::post('reset-password', [\App\Http\Controllers\Frontend\Auth\NewPasswordController::class, 'store'])->name('password.store');
});
Route::get('/auth/microsoft', [\App\Http\Controllers\SocialAuthController::class, 'redirectToMicrosoft'])->name('auth.microsoft');
Route::get('/auth/microsoft/callback', [\App\Http\Controllers\SocialAuthController::class, 'handleMicrosoftCallback']);

Route::get('/', function () {
  return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
  Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
  })->name('dashboard');
  Route::post('logout', [\App\Http\Controllers\Frontend\Auth\AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');
  Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
      return Inertia::render('Admin/Dashboard');
    })->name('dashboard');
    Route::get('/search', function () {
      return Inertia::render('Admin/Search/Index', [
        'filters' => request()->all('q')
      ]);
    })->name('search');
    Route::get('/books', function () {
      return Inertia::render('Admin/Books/Index');
    })->name('books.index');
    Route::get('/categories', function () {
      return Inertia::render('Admin/Categories/Index');
    })->name('categories.index');
    Route::get('/authors', function () {
      return Inertia::render('Admin/Authors/Index');
    })->name('authors.index');
    Route::get('/publishers', function () {
      return Inertia::render('Admin/Publishers/Index');
    })->name('publishers.index');
    Route::get('/readers', function () {
      return Inertia::render('Admin/Readers/Index');
    })->name('readers.index');
    Route::get('/cards', function () {
      return Inertia::render('Admin/Cards/Index');
    })->name('cards.index');
    Route::prefix('library')->name('library.')->group(function () {
      Route::get('/slips', function () {
        return Inertia::render('Admin/Library/Slips');
      })->name('slips');
      Route::get('/liquidation', function () {
        return Inertia::render('Admin/Library/Liquidation');
      })->name('liquidation');
      Route::get('/inventory', function () {
        return Inertia::render('Admin/Library/Inventory');
      })->name('inventory');
    });

    Route::prefix('loans')->name('loans.')->group(function () {
      Route::get('/', function () {
        return Inertia::render('Admin/Loans/Index');
      })->name('index');
      Route::get('/extensions', function () {
        return Inertia::render('Admin/Loans/Extensions');
      })->name('extensions');
      Route::get('/onsite', function () {
        return Inertia::render('Admin/Loans/OnSite');
      })->name('onsite');
      Route::get('/penalties', function () {
        return Inertia::render('Admin/Loans/Penalties');
      })->name('penalties');
    });

    Route::get('/stats', function () {
      return Inertia::render('Admin/Stats/Index');
    })->name('stats.index');

    Route::get('/users', function () {
      return Inertia::render('Admin/Users/Index');
    })->name('users.index');

    Route::get('/profile', function () {
      return Inertia::render('Admin/Profile');
    })->name('profile');

    Route::get('/settings', function () {
      return Inertia::render('Admin/Settings');
    })->name('settings');
  });
});
