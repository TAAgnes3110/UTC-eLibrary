<?php

use App\Enums\RoleType;
use App\Http\Controllers\Frontend\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

require __DIR__ . '/auth.php';
require __DIR__ . '/reader.php';

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = request()->user();
        $staffRoles = RoleType::staffRoles();
        $roleValue = $user->user_type instanceof RoleType ? $user->user_type->value : ($user->user_type ?? null);
        $isStaff = $roleValue && in_array($roleValue, $staffRoles, true);
        return redirect()->route($isStaff ? 'admin.dashboard' : 'library.dashboard');
    })->name('dashboard');

    Route::post('logout', [\App\Http\Controllers\Frontend\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');

    require __DIR__ . '/admin.php';
});
