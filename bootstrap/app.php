<?php

use App\Http\Middleware\ApplyProductionHeaders;
use App\Http\Middleware\EnsureUserIsAdmin;
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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(ApplyProductionHeaders::class);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        $middleware->redirectUsersTo('/customer/dashboard');

        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->is('admin/*')) {
                return route('login');
            }

            if ($request->is('customer/dashboard')) {
                $intended = $request->getRequestUri();

                if ($intended !== '/customer/dashboard') {
                    return '/login?'.http_build_query(['next' => $intended], '', '&', PHP_QUERY_RFC3986);
                }
            }

            return route('customer.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
