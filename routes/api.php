<?php

use App\Enums\RoleType;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\ClassificationController;
use App\Http\Controllers\Api\ClassificationDetailController;
use App\Http\Controllers\Api\DigitalAssetController;
use App\Http\Controllers\Api\EmailOTPController;
use App\Http\Controllers\Api\FacultyController;
use App\Http\Controllers\Api\LibraryCard\LibraryCardGuestController;
use App\Http\Controllers\Api\LibraryCard\LibraryCardStaffController;
use App\Http\Controllers\Api\LibraryCard\MeLibraryCardController;
use App\Http\Controllers\Api\LibraryCardController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\LoanPoliciesController;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PublisherController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Middleware\LogApiRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

// Health check: GET /api/health (no auth, no throttle) - Logging & Monitoring
Route::get('health', function () {
    $checks = ['database' => false, 'cache' => false, 'redis' => null];
    try {
        DB::connection()->getPdo();
        $checks['database'] = true;
    } catch (Throwable) {
        //
    }
    $driver = config('cache.default');
    try {
        Cache::store($driver)->put('health_ping', true, 10);
        $checks['cache'] = Cache::store($driver)->get('health_ping') === true;
    } catch (Throwable) {
        //
    }
    if ($driver === 'redis') {
        try {
            Redis::connection()->ping();
            $checks['redis'] = true;
        } catch (Throwable) {
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
        Route::put('password', [ProfileController::class, 'updatePassword']);
        Route::post('library-card', [MeLibraryCardController::class, 'store']);
    });

    Route::middleware(['throttle:auth'])->group(function () {
        Route::post('library-cards/guest-register', [LibraryCardGuestController::class, 'store']);
    });

    Route::get('master-data', [MasterDataController::class, 'index'])->middleware(['init']);

    Route::group(['middleware' => ['init']], function () {
        Route::middleware(['role_or_permission:'.RoleType::SUPER_ADMIN->value.'|role_prefix_'.RoleType::ADMIN->value.'|role_prefix_'.RoleType::LIBRARIAN->value])->group(function () {
            Route::apiResource('faculties', FacultyController::class);

            Route::group(['prefix' => '/users'], function () {
                Route::get('/', [UserController::class, 'index']);
                Route::get('/export', [UserController::class, 'exportUsers']);
                Route::get('/trash', [UserController::class, 'trash']);
                Route::post('/avatar-bulk', [UserController::class, 'bulkUpdateAvatar']);
                Route::post('/', [UserController::class, 'store']);
                Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
                Route::post('/{id}/avatar', [UserController::class, 'updateAvatar']);
                Route::get('/{user}', [UserController::class, 'show']);
                Route::put('/{user}', [UserController::class, 'update']);
                Route::delete('/{user}', [UserController::class, 'destroy']);
                Route::post('/restore', [UserController::class, 'restoreMany']);
                Route::post('/restore/{id}', [UserController::class, 'restore']);
                Route::post('/force', [UserController::class, 'forceDeleteMany']);
                Route::delete('/force', [UserController::class, 'forceDeleteMany']);
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

            Route::group(['prefix' => '/classifications'], function () {
                Route::get('/', [ClassificationController::class, 'index']);
                Route::get('/list', [ClassificationController::class, 'list']);
                Route::get('/{classification}/details', [ClassificationDetailController::class, 'listByClassification']);
                Route::get('/import-template', [ClassificationController::class, 'downloadImportTemplate']);
                Route::post('/', [ClassificationController::class, 'store']);
                Route::get('/{classification}', [ClassificationController::class, 'show']);
                Route::put('/{classification}', [ClassificationController::class, 'update']);
                Route::delete('/{classification}', [ClassificationController::class, 'destroy']);
            });

            Route::group(['prefix' => '/classification-details'], function () {
                Route::get('/', [ClassificationDetailController::class, 'index']);
                Route::get('/import-template', [ClassificationDetailController::class, 'downloadImportTemplate']);
                Route::post('/', [ClassificationDetailController::class, 'store']);
                Route::get('/{classification_detail}', [ClassificationDetailController::class, 'show']);
                Route::put('/{classification_detail}', [ClassificationDetailController::class, 'update']);
                Route::delete('/{classification_detail}', [ClassificationDetailController::class, 'destroy']);
            });

            Route::group(['prefix' => '/warehouses'], function () {
                Route::get('/', [WarehouseController::class, 'index']);
                Route::get('/export', [WarehouseController::class, 'exportWarehouses']);
                Route::get('/import-template', [WarehouseController::class, 'downloadImportTemplate']);
                Route::post('/import', [WarehouseController::class, 'import']);
                Route::get('/trash', [WarehouseController::class, 'trash']);
                Route::post('/restore', [WarehouseController::class, 'restoreMany']);
                Route::post('/restore/{id}', [WarehouseController::class, 'restore']);
                Route::post('/force', [WarehouseController::class, 'forceDeleteMany']);
                Route::delete('/force', [WarehouseController::class, 'forceDeleteMany']);
                Route::delete('/force/{id}', [WarehouseController::class, 'forceDelete']);
                Route::post('/{id}/toggle-status', [WarehouseController::class, 'toggleStatus']);
                Route::post('/', [WarehouseController::class, 'store']);
                Route::get('/{warehouse}', [WarehouseController::class, 'show']);
                Route::put('/{warehouse}', [WarehouseController::class, 'update']);
                Route::delete('/{warehouse}', [WarehouseController::class, 'destroy']);
            });

            Route::group(['prefix' => 'loan-policies'], function () {
                Route::get('/', [LoanPoliciesController::class, 'index']);
                Route::post('/', [LoanPoliciesController::class, 'store']);
                Route::put('/{loan_policy}', [LoanPoliciesController::class, 'update']);
            });

            Route::group(['prefix' => 'loans'], function () {
                Route::get('/', [LoanController::class, 'index']);
                Route::get('/export', [LoanController::class, 'export']);
                Route::post('/', [LoanController::class, 'store']);
                Route::get('/{loan}', [LoanController::class, 'show']);
                Route::put('/{loan}', [LoanController::class, 'update']);
                Route::delete('/{loan}', [LoanController::class, 'destroy']);
                Route::post('/{loan}/return', [LoanController::class, 'return']);
            });

            Route::group(['prefix' => '/authors'], function () {
                Route::get('/', [AuthorController::class, 'index']);
                Route::post('/', [AuthorController::class, 'store']);
                Route::put('/{author}', [AuthorController::class, 'update']);
                Route::delete('/{author}', [AuthorController::class, 'destroy']);
            });

            Route::group(['prefix' => '/publishers'], function () {
                Route::get('/', [PublisherController::class, 'index']);
                Route::post('/', [PublisherController::class, 'store']);
                Route::put('/{publisher}', [PublisherController::class, 'update']);
                Route::delete('/{publisher}', [PublisherController::class, 'destroy']);
            });

            Route::group(['prefix' => 'library-cards'], function () {
                Route::get('export', [LibraryCardController::class, 'export']);
                Route::get('trash', [LibraryCardController::class, 'trash']);
                Route::post('restore', [LibraryCardController::class, 'restoreMany']);
                Route::post('restore/{id}', [LibraryCardController::class, 'restore']);
                Route::post('force', [LibraryCardController::class, 'forceDeleteMany']);
                Route::delete('force', [LibraryCardController::class, 'forceDeleteMany']);
                Route::delete('force/{id}', [LibraryCardController::class, 'forceDelete']);
                Route::post('{library_card}/approve-review', [LibraryCardStaffController::class, 'approveReview']);
                Route::post('{library_card}/reject-review', [LibraryCardStaffController::class, 'rejectReview']);
                Route::post('{library_card}/photo', [LibraryCardController::class, 'updatePhoto']);
                Route::get('/', [LibraryCardController::class, 'index']);
                Route::post('/', [LibraryCardController::class, 'store']);
                Route::get('{library_card}', [LibraryCardController::class, 'show']);
                Route::put('{library_card}', [LibraryCardController::class, 'update']);
                Route::delete('{library_card}', [LibraryCardController::class, 'destroy']);
            });

            Route::group(['prefix' => '/books'], function () {
                Route::get('/', [BookController::class, 'index']);
                Route::get('/trash', [BookController::class, 'trash']);
                Route::get('/import-template', [BookController::class, 'downloadImportTemplate']);
                Route::get('/export', [BookController::class, 'export']);
                Route::post('/import', [BookController::class, 'import']);
                Route::post('/', [BookController::class, 'store']);
                Route::post('/{book}/digital-assets', [DigitalAssetController::class, 'store']);
                Route::delete('/{book}/digital-assets/{digital_asset}', [DigitalAssetController::class, 'destroy']);
                Route::get('/{book}', [BookController::class, 'show']);
                Route::put('/{book}', [BookController::class, 'update']);
                Route::delete('/{book}', [BookController::class, 'destroy']);
                Route::post('/restore', [BookController::class, 'restoreMany']);
                Route::post('/restore/{id}', [BookController::class, 'restore']);
                Route::post('/force', [BookController::class, 'forceDeleteMany']);
                Route::delete('/force', [BookController::class, 'forceDeleteMany']);
                Route::delete('/force/{id}', [BookController::class, 'forceDelete']);
                Route::post('/{id}/image', [BookController::class, 'updateImage']);
                Route::post('/image-bulk', [BookController::class, 'bulkUpdateImage']);
            });

        });
    });
});
