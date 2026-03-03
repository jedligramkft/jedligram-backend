<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Policies\ThreadPolicy;
use App\Models\Thread;


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
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Thread::class, ThreadPolicy::class);

        // Rate-limiter for the sysadmin panel:
        // - login page: max 10 attempts per minute per IP (brute-force protection)
        // - protected actions: max 30 requests per minute per IP
        RateLimiter::for('sysadmin.login', function ($request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('sysadmin', function ($request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
