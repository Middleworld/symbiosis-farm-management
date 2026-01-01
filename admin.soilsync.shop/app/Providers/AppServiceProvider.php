<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Http\ViewComposers\BrandingComposer;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // No longer binding DirectDatabaseService - it should be removed
        // $this->app->singleton(\App\Services\DirectDatabaseService::class, \App\Services\WpApiService::class);

        // Bind Passport contracts
        $this->app->bind(
            \Laravel\Passport\Contracts\AuthorizationViewResponse::class,
            function () {
                return new \Laravel\Passport\Http\Responses\SimpleViewResponse('passport::authorize');
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Passport view namespace
        \View::addNamespace('passport', resource_path('views/passport'));
        
        // Force HTTPS scheme for all URLs
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
        
        // Use custom Bootstrap 5 pagination view
        \Illuminate\Pagination\Paginator::defaultView('pagination::bootstrap-5');

        // Share branding data with all views
        View::composer('*', BrandingComposer::class);
    }
}
