<?php

namespace Core\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Core\Listeners\QueueListener;
use Event;
class ConsoleServiceProvider extends ServiceProvider
{

    protected $commands =
    [
        'Core\Console\Commands\Api\Replay',
        'Core\Console\Commands\Cli\ClearCache',
        'Core\Console\Commands\Cli\Update',
        'Core\Console\Commands\Phinx\Create',
        'Core\Console\Commands\Phinx\Migrate',
        'Core\Console\Commands\Phinx\Rollback',
        'Core\Console\Commands\Phinx\Status',
        'Core\Console\Commands\Redis\Clear',
        'Core\Console\Commands\Table\Cache',
        'Core\Console\Commands\Table\Clear',
        'Core\Console\Commands\Cli\GenerateCron',
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);
    }
    public function boot()
    {
        //in appservice 
        //parent::boot();
        // Event::listen('Illuminate\Queue\Events\JobProcessing', QueueListener::class);
        // Event::listen('Illuminate\Queue\Events\JobProcessed', QueueListener::class);
        // //not needed=> jobException gives failed info
        // //Event::listen('Illuminate\Queue\Events\JobFailed', QueueListener::class);
        // Event::listen('Illuminate\Queue\Events\JobExceptionOccurred', QueueListener::class);
    }
}
