<?php

use App\Http\Middleware\CheckEmployeeRole;
use App\Http\Middleware\CustomerMiddleware;
use App\Http\Middleware\EmployeeMiddleware;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\VerifyPayOSWebhookSource;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: [
            'token',
            'refresh_token',
        ]);
 
        $middleware->alias([
            'auth.cookie'            => \App\Http\Middleware\AuthenticateFromCookie::class,
            'role'                   => \App\Http\Middleware\RoleMiddleware::class,
            'redirectifauthenticated' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'payos.webhook'          => \App\Http\Middleware\VerifyPayOSWebhookSource::class,
        ]);

        // Kích hoạt nhận diện Cookie cho các route API
        $middleware->api(prepend: [
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \App\Http\Middleware\AuthenticateFromCookie::class,
        ]);

        $middleware->group('customer', [
            \App\Http\Middleware\AuthenticateFromCookie::class,
            'role:customer',
        ]);

        $middleware->group('admin', [
            'role:admin,manager',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Dữ liệu không hợp lệ.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Không tìm thấy dữ liệu yêu cầu.',
                ], 404);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Phiên đăng nhập đã hết hạn hoặc không hợp lệ.',
                ], 401);
            }
        });
    })->create();
