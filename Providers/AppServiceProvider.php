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
        $this->app->singleton('job', '\Core\Queue\FJob');
        $this->app->singleton('notification', '\Core\Services\Notification');
        $this->app->singleton('consolelog', '\Core\Services\ConsoleLog');
    }
}

