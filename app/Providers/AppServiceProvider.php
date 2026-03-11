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
use LdapRecord\Connection;
use LdapRecord\Container;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

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
      
        $this->bootLdap();
      
        // Rate-limiter for the sysadmin panel:
        // - login page: max 10 attempts per minute per IP (brute-force protection)
        // - protected actions: max 30 requests per minute per IP
        RateLimiter::for('sysadmin.login', function ($request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('sysadmin', function ($request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        /*
            Dedoc scramble to include bearer token in the requests
        */
        Scramble::configure()
        ->withDocumentTransformers(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer')
            );
        });     
    }

    /**
     * Register LDAPRecord connections from config/ldap.php.
     *
     * Since we use the base `ldaprecord` package (not `ldaprecord-laravel`),
     * connections must be added to the Container manually.
     */
    protected function bootLdap(): void
    {
        $defaultName = config('ldap.default', 'default');

        foreach (config('ldap.connections', []) as $name => $settings) {
            $connection = new Connection($settings);

            if ($name === $defaultName) {
                Container::addConnection($connection);          // default connection
            } else {
                Container::addConnection($connection, $name);   // named connection
            }
        }
    }
}
