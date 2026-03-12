<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailOTPController;
use App\Http\Controllers\Api\FacultyController;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
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

    Route::group(['prefix' => 'me', 'middleware' => ['init']], function () {
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
    });

    Route::get('master-data', [MasterDataController::class, 'index'])->middleware(['init']);

    Route::group(['middleware' => ['init']], function () {
        Route::middleware(['role_or_permission:' . RoleType::SUPER_ADMIN->value . '|role_prefix_' . RoleType::ADMIN->value . '|role_prefix_' . RoleType::LIBRARIAN->value])->group(function () {
            Route::apiResource('faculties', FacultyController::class);

            Route::group(['prefix' => '/users'], function () {
                Route::get('/', [UserController::class, 'index']);
                Route::get('/trash', [UserController::class, 'trash']);
                Route::post('/', [UserController::class, 'store']);
                Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
                Route::post('/{id}/avatar', [UserController::class, 'updateAvatar']);
                Route::get('/{user}', [UserController::class, 'show']);
                Route::put('/{user}', [UserController::class, 'update']);
                Route::delete('/{user}', [UserController::class, 'destroy']);
                Route::post('/restore/{id}', [UserController::class, 'restore']);
                Route::delete('/force/{id}', [UserController::class, 'forceDelete']);
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
