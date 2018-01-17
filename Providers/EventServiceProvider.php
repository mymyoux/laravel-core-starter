<?php

namespace Core\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Core\Listeners\CacheListener;
use App\Http\Controllers\GithubController;
use Core\Traits\Cache\Listener;
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
         \SocialiteProviders\Manager\SocialiteWasCalled::class => [
            'SocialiteProviders\Google\GoogleExtendSocialite@handle',
            'SocialiteProviders\Coinbase\CoinbaseExtendSocialite@handle',
            'Core\Providers\CoinbaseServiceProvider@handle'
        ],
    ];
     protected $subscribe = [
         'Core\Events\EventEventSubscriber'
    ];
    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    { 
        parent::boot();
        if(config('stats.redis'))
        {
            Event::listen('Illuminate\Cache\Events\CacheMissed', CacheListener::class);
            Event::listen('Illuminate\Cache\Events\CacheHit', CacheListener::class);
        }
        if(class_exists(GithubController::class))
            Event::listen('Core\Events\SocialScopeChangedEvent', GithubController::class.'@scopeChanged');
        Event::listen('eloquent.saved:*', Listener::class.'@saved');
        Event::listen('eloquent.deleted:*', Listener::class.'@deleted');
                //
    }
}
