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
        $this->bootQuery();
    }

    protected function bootQuery()
    {
        DB::enableQueryLog();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerStats();
        $this->registerApi();
        $this->registerJob();
        $this->registerNoticication();
        $this->registerLogger();
    }

    protected function registerStats()
    {
        $this->app->singleton('stats', '\Core\Services\Stats');
    }

    protected function registerApi()
    {
        $this->app->singleton('api', '\Core\Api\Api');
    }

    protected function registerJob()
    {
        $this->app->singleton('job', '\Core\Services\Job');
    }

    protected function registerNoticication()
    {
        $this->app->singleton('notification', '\Core\Services\Notification');
    }

    protected function registerLogger()
    {
        $this->app->singleton('logger', '\Core\Services\Logger');
    }
}

