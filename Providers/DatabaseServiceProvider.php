<?php

namespace Core\Providers;
use Core\Database\Connectors\ConnectionFactory;
use Illuminate\Support\ServiceProvider;
use DB;
class DatabaseServiceProvider extends \Illuminate\Database\DatabaseServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }
    /**
     * Called by the parent class
     */
    protected function registerConnectionServices()
    {
        $this->app->singleton('table', '\Tables\Table');

        
        parent::registerConnectionServices();
        // The connection factory is used to create the actual connection instances on
        // the database. We will inject the factory into the manager so that it may
        // make the connections while they are actually needed and not of before.
        $this->app->singleton('db.factory', function ($app) {
            return new ConnectionFactory($app);
        });
    }
}
