<?php

namespace Core\Providers;

use Illuminate\Support\ServiceProvider;
use DB;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        DB::enableQueryLog();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        
        $this->app->singleton('stats', '\Core\Services\Stats');
        $this->app->singleton('api', '\Core\Api\Api');
        $this->app->singleton('tables', '\Core\Services\Tables');
    }
}
