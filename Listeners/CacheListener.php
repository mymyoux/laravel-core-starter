<?php
namespace Core\Listeners;
use Illuminate\Cache\Events\CacheEvent;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;

class CacheListener
{
	protected static $missed = [];
	protected static $hits = [];
	 /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  OrderShipped  $event
     * @return void
     */
    public function handle(CacheEvent $event)
    {
    	if($event instanceof CacheHit)
    	{
    		CacheListener::$hits[] = $event->key;
    	}
    	if($event instanceof CacheMissed)
    	{
    		CacheListener::$missed[] = $event->key;
    	}
    }
    public static function getQueryLog()
    {
    	return ["hits"=>CacheListener::$hits, "missed"=>CacheListener::$missed];
    }

}
