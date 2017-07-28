<?php

namespace Core\Providers;

use Illuminate\Support\ServiceProvider;
use DB;

use Core\Listeners\QueueListener;
use Event;
use Logger;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
       Event::listen('Illuminate\Queue\Events\JobProcessing', QueueListener::class);
        Event::listen('Illuminate\Queue\Events\JobProcessed', QueueListener::class);
        //not needed=> jobException gives failed info
        //Event::listen('Illuminate\Queue\Events\JobFailed', QueueListener::class);
        Event::listen('Illuminate\Queue\Events\JobExceptionOccurred', QueueListener::class);


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
        $this->registerNotification();
        $this->registerLogger();
        $this->registerMail();
        $this->registerCrawl();
        $this->registerTranslation();
        $this->registerGMap();
        $this->registerAction();
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
    protected function registerMail()
    {
        $this->app->singleton('mail', '\Core\Services\Mail');
    }

    protected function registerAction()
    {
        $this->app->singleton('action', '\Core\Services\Action');
    }

    protected function registerNotification()
    {
        $this->app->singleton('notification', '\Core\Services\Notification');
    }

    protected function registerLogger()
    {
        $this->app->singleton('logger', '\Core\Services\Logger');
    }
    protected function registerCrawl()
    {
        $this->app->singleton('crawl', '\Core\Services\Crawl');
    }
    protected function registerTranslation()
    {
        $this->app->singleton('translate', '\Core\Services\Translate');
    }
    protected function registerGMap()
    {
        $this->app->singleton('gmap', '\Core\Services\GMap');
    }
}

