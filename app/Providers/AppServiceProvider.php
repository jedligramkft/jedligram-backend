<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Policies\ThreadPolicy;
use App\Models\Thread;
use LdapRecord\Connection;
use LdapRecord\Container;


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
