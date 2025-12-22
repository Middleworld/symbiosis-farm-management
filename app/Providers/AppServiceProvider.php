<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // No longer binding DirectDatabaseService - it should be removed
        // $this->app->singleton(\App\Services\DirectDatabaseService::class, \App\Services\WpApiService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS scheme for all URLs
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
        
        // Use custom Bootstrap 5 pagination view
        \Illuminate\Pagination\Paginator::defaultView('pagination::bootstrap-5');
    }
}
