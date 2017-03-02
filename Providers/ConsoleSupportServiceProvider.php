<?php

namespace Core\Providers;

use Illuminate\Foundation\Providers\ArtisanServiceProvider;
use Illuminate\Foundation\Providers\ComposerServiceProvider;
use Illuminate\Support\AggregateServiceProvider;
use Illuminate\Database\MigrationServiceProvider;

class ConsoleSupportServiceProvider extends AggregateServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * The provider class names.
     *
     * @var array
     */
    protected $providers = [
        //ArtisanServiceProvider::class,
        MigrationServiceProvider::class,
        ComposerServiceProvider::class,
        ConsoleServiceProvider::class,
    ];
    protected $artisanProviders = [
        ArtisanServiceProvider::class,
        MigrationServiceProvider::class,
        ComposerServiceProvider::class,
        ConsoleServiceProvider::class,
    ];
    public function register()
    {
        $this->instances = [];

        $artisan = !!env('ARTISAN', True);
        $providers = $artisan?$this->artisanProviders:$this->providers;
        foreach ($providers as $provider) {
            $this->instances[] = $this->app->register($provider);
        }
    }
}
