<?php

use App\Http\Controllers\Backend\AuthController;
use App\Http\Controllers\Backend\AuthorController;
use App\Http\Controllers\Backend\BookController;
use App\Http\Controllers\Backend\EmailOTPController;
use App\Http\Controllers\Backend\PermissionController;
use App\Http\Controllers\Backend\RoleController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Middleware\LogApiRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

use App\Enums\RoleType;

// Health check: GET /api/health (no auth, no throttle) - Logging & Monitoring
Route::get('health', function () {
    $checks = ['database' => false, 'cache' => false, 'redis' => null];
    try {
        DB::connection()->getPdo();
        $checks['database'] = true;
    } catch (\Throwable) {
        //
    }
    $driver = config('cache.default');
    try {
        Cache::store($driver)->put('health_ping', true, 10);
        $checks['cache'] = Cache::store($driver)->get('health_ping') === true;
    } catch (\Throwable) {
        //
    }
    if ($driver === 'redis') {
        try {
            \Illuminate\Support\Facades\Redis::connection()->ping();
            $checks['redis'] = true;
        } catch (\Throwable) {
            $checks['redis'] = false;
        }
    }
    $ok = $checks['database'] && $checks['cache'] && ($checks['redis'] !== false);
    return response()->json([
        'status' => $ok ? 'ok' : 'degraded',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ], $ok ? 200 : 503);
})->withoutMiddleware([LogApiRequests::class]);

Route::prefix('v1')->group(function () {
    Route::middleware(['throttle:auth'])->group(function () {
        Route::group(['prefix' => 'auth'], function () {
            Route::post('login', [AuthController::class, 'login']);
            Route::post('register', [AuthController::class, 'register']);
            Route::post('verify-otp', [AuthController::class, 'verifyRegister']);
            Route::post('resend-otp', [EmailOTPController::class, 'store']);
            Route::post('reset-password', [AuthController::class, 'resetPassword']);
        });
    });

    Route::post('auth/refresh', [AuthController::class, 'refresh'])->middleware('throttle:refresh');

    Route::group(['prefix' => 'auth', 'middleware' => ['init']], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
    });

    Route::group(['middleware' => ['init']], function () {
        Route::middleware(['role_or_permission:' . RoleType::SUPER_ADMIN->value . '|role_prefix_' . RoleType::ADMIN->value . '|role_prefix_' . RoleType::LIBRARIAN->value])->group(function () {
            Route::group(['prefix' => '/users'], function () {
                Route::get('/', [UserController::class, 'index']);
                Route::get('/trash', [UserController::class, 'trash']);
                Route::post('/', [UserController::class, 'store']);
                Route::put('/{user}', [UserController::class, 'update']);
                Route::delete('/{user}', [UserController::class, 'destroy']);
                Route::post('/restore/{id}', [UserController::class, 'restore']);
                Route::delete('/force/{id}', [UserController::class, 'forceDelete']);
            });

            Route::group(['prefix' => '/authors'], function () {
                Route::get('/', [AuthorController::class, 'index']);
                Route::get('/trash', [AuthorController::class, 'trash']);
                Route::post('/', [AuthorController::class, 'store']);
                Route::get('/{author}', [AuthorController::class, 'show']);
                Route::post('/import', [AuthorController::class, 'import']);
                Route::put('/{author}', [AuthorController::class, 'update']);
                Route::delete('/{author}', [AuthorController::class, 'destroy']);
                Route::post('/restore/{id}', [AuthorController::class, 'restore']);
                Route::delete('/force/{id}', [AuthorController::class, 'forceDelete']);
            });

            Route::group(['prefix' => '/books'], function () {
                Route::get('/', [BookController::class, 'index']);
                Route::get('/trash', [BookController::class, 'trash']);
                Route::post('/', [BookController::class, 'store']);
                Route::put('/{book}', [BookController::class, 'update']);
                Route::delete('/{book}', [BookController::class, 'destroy']);
                Route::post('/import', [BookController::class, 'import']);
                Route::post('/restore/{id}', [BookController::class, 'restore']);
                Route::delete('/force/{id}', [BookController::class, 'forceDelete']);
            });

            Route::group(['prefix' => '/roles'], function () {
                Route::get('/', [RoleController::class, 'index']);
                Route::post('/', [RoleController::class, 'store']);
                Route::get('/{id}', [RoleController::class, 'show']);
                Route::put('/{id}', [RoleController::class, 'update']);
                Route::delete('/{id}', [RoleController::class, 'destroy']);
                Route::post('/{id}/permissions', [RoleController::class, 'addPermission']);
                Route::delete('/{id}/permissions', [RoleController::class, 'removePermission']);
            });

            Route::group(['prefix' => '/permissions'], function () {
                Route::get('/', [PermissionController::class, 'index']);
                Route::post('/', [PermissionController::class, 'store']);
            });
        });
    });
});
