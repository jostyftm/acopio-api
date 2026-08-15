<?php

use App\Exceptions\ApiHandlerException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckForAnyScope;
use Laravel\Passport\Http\Middleware\CheckScopes;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('api')
                ->prefix('api')
                ->group(function (): void {
                    foreach (['v1'] as $version) {
                        Route::prefix($version)->group(base_path("routes/api/{$version}.php"));
                    }
                });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'scopes' => CheckScopes::class,
            'scope' => CheckForAnyScope::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        ApiHandlerException::register($exceptions);
    })->create();
