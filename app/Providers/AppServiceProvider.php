<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure rate limiters for API
        RateLimiter::for('api-login', function ($request) {
            return Limit::perMinute(5)->by($request->input('email').'|'.$request->ip());
        });

        // Gate for API documentation access
        Gate::define('view-api-docs', function ($user) {
            return $user->email === 'jonathanrr839@gmail.com';
        });
    }
}
