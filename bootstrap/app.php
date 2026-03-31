<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CustomerMiddleware;
use App\Http\Middleware\EmployeeMiddleware;
use App\Http\Middleware\CheckEmployeeRole;
use App\Http\Middleware\RedirectIfAuthenticated;
use Symfony\Component\Routing\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
        $middleware->alias([
            'employee'=> EmployeeMiddleware::class,
            'customer'=> CustomerMiddleware::class,
            'employeerole'=> CheckEmployeeRole::class,
            'redirectifauthenticated'=> RedirectIfAuthenticated::class,
        ]);
        $middleware->group('customer',[
            CustomerMiddleware::class,
            redirectifauthenticated::class
        ]);
        $middleware->group('employee',[
            EmployeeMiddleware::class,
            CheckEmployeeRole::class,
            redirectifauthenticated::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
