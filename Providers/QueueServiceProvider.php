<?php

namespace Core\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Queue\Connectors\NullConnector;
use Illuminate\Queue\Connectors\SyncConnector;
use Illuminate\Queue\Connectors\RedisConnector;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Queue\Connectors\DatabaseConnector;
use Illuminate\Queue\Failed\NullFailedJobProvider;
use Illuminate\Queue\Failed\DatabaseFailedJobProvider;
use Illuminate\Queue\QueueManager;
//use Illuminate\Queue\Connectors\BeanstalkdConnector;
use Core\Queue\Connectors\BeanstalkdConnector;

use Core\Queue\Worker;
use Illuminate\Queue\Listener;
use Illuminate\Queue\QueueServiceProvider as BaseQueueServiceProvider;


class QueueServiceProvider extends BaseQueueServiceProvider
{
     protected function registerBeanstalkdConnector($manager)
    {
        $manager->addConnector('beanstalkd', function () {
            return new BeanstalkdConnector;
        });
    }
        /**
     * Register the queue worker.
     *
     * @return void
     */
    protected function registerWorker()
    {
        $this->app->singleton('queue.worker', function () {
            return new Worker(
                $this->app['queue'], $this->app['events'], $this->app[ExceptionHandler::class]
            );
        });
    }
}
