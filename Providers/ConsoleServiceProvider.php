<?php

namespace Core\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
class ConsoleServiceProvider extends ServiceProvider
{

    protected $commands =
    [
         'Core\Console\Commands\Cli\ClearCache',
         'Core\Console\Commands\Cli\Update',
         'Core\Console\Commands\Phinx\Create',
         'Core\Console\Commands\Phinx\Migrate',
         'Core\Console\Commands\Phinx\Rollback',
         'Core\Console\Commands\Phinx\Status',
         'Core\Console\Commands\Redis\Clear',
         'Core\Console\Commands\Table\Cache',
         'Core\Console\Commands\Table\Clear'
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
}
