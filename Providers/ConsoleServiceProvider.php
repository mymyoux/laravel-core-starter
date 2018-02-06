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
        'Core\Console\Commands\Api\Call',
        'Core\Console\Commands\Api\Replay',
        'Core\Console\Commands\Cli\ClearCache',
        'Core\Console\Commands\Cli\Config',
        'Core\Console\Commands\Cli\GenerateCron',
        'Core\Console\Commands\Cli\Project',
        'Core\Console\Commands\Cli\Update',
        'Core\Console\Commands\Cli\Certificate',
        'Core\Console\Commands\Config',
        'Core\Console\Commands\Db\Clean',
        'Core\Console\Commands\Doc\Generate',
        'Core\Console\Commands\Notifications\Send',
        'Core\Console\Commands\Phinx\Create',
        'Core\Console\Commands\Phinx\Migrate',
        'Core\Console\Commands\Phinx\Rollback',
        'Core\Console\Commands\Phinx\Status',
        'Core\Console\Commands\Sass\Cache',
        'Core\Console\Commands\Sass\Clear',
        'Core\Console\Commands\Sass\Compile',
        'Core\Console\Commands\Sass\Watch',
        'Core\Console\Commands\Supervisor\Config',
        'Core\Console\Commands\Supervisor\Reread',
        'Core\Console\Commands\Supervisor\Restart',
        'Core\Console\Commands\Supervisor\Update',
        'Core\Console\Commands\Redis\Clear',
        'Core\Console\Commands\Table\Cache',
        'Core\Console\Commands\Table\Clear',
        'Core\Console\Commands\Tsc\Cache',
        'Core\Console\Commands\Tsc\Clear',
        'Core\Console\Commands\Tsc\Compile',
        'Core\Console\Commands\Tsc\Watch',
        'Core\Console\Commands\Template\Cache',
        'Core\Console\Commands\Template\Clear'
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
