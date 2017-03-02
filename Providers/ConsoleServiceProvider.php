<?php

namespace Core\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
class ConsoleServiceProvider extends ServiceProvider
{

    protected $commands =
    [
         'Core\Console\Commands\Redis\Clear',
         'Core\Console\Commands\Cli\Update'
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
