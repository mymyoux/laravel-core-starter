<?php

namespace Core\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Core\Auth\Guard;
use Auth;
class AuthServiceProvider extends ServiceProvider
{

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        Auth::extend('custom', function ($app, $name, array $config) {
            // Return an instance of Illuminate\Contracts\Auth\Guard...
            return new Guard("web",Auth::createUserProvider($config['provider']), app('session.store'));
        });
        //
    }
}
