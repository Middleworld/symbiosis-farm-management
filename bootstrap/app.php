<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/pos.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuthentication::class,
            'pos.auth' => \App\Http\Middleware\PosAuthentication::class,
            'verify.wc.api.token' => \App\Http\Middleware\VerifyWooCommerceApiToken::class,
        ]);
        
        // Add TrustProxies middleware to web middleware group
        $middleware->web(prepend: [
            \App\Http\Middleware\TrustProxies::class,
        ]);
        
        // Exclude webhook routes from CSRF protection
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'pos/*',
            'admin/companies-house/accounts/generate',
        ]);
        
        // Define a POS middleware group without CSRF protection
        $middleware->group('pos', [
            \App\Http\Middleware\PosAuthentication::class,
            \App\Http\Middleware\TrustProxies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
