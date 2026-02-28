<?php

use App\Http\Middleware\CheckLogin;
use App\Http\Middleware\CheckRoleOrPermission;
use App\Http\Middleware\Init;
use App\Http\Middleware\LogApiRequests;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->priority([
            Init::class,
        ]);
        $middleware->alias([
            'init' => Init::class,
            'login' => CheckLogin::class,
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
                return \App\Helpers\ApiResponse::error($e->getMessage() ?: __('Unauthenticated.'), 401);
            }
        });
    })->create();
