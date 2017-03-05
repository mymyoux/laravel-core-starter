<?php
namespace Core\App;

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
}
