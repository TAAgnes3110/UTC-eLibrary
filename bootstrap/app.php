<?php

use App\Helpers\ApiResponse;
use App\Http\Middleware\CheckRoleOrPermission;
use App\Http\Middleware\Init;
use App\Http\Middleware\LogApiRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->priority([
            Init::class,
        ]);
        $middleware->alias([
            'init' => Init::class,
            'role_or_permission' => CheckRoleOrPermission::class,
            'log.api' => LogApiRequests::class,
        ]);
        $middleware->api(append: [
            'throttle:api',
            LogApiRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error($e->getMessage() ?: __('Bạn cần đăng nhập để tiếp tục.'), 401);
            }
        });
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error($e->getMessage() ?: __('Không đủ quyền thực hiện thao tác.'), 403);
            }
        });
    })->create();
