<?php
namespace Core\App;
use Core\Services\IP;
class Application extends \Illuminate\Foundation\Application
{
	protected $_isCron;
	/**
     * Determine if we are running in cron.
     *
     * @return bool
     */
    public function runningInCron()
    {
    	return env('ENV_CRON', False) == True;
    }
    public function runningInQueue()
    {
        //TODO:implement
        return env('ENV_QUEUE', False) == True;
    }
    public function ip()
    {
        return IP::getRequestIP();
    }
}
