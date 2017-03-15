<?php

namespace Core\Jobs;

use Core\Queue\JobHandler;
use Api as ApiService;
use Logger;
use Auth;
class Api extends JobHandler
{
    protected $add_params;
    protected $api_user;
    protected $params;
    protected $method;
    protected $path;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }
     public static function getDelayRetry()
    {
        return 0;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(Auth::check())
        {
            Logger::warn('queue user:'.Auth::id());
        }
        $result = ApiService::path($this->path)->method($this->method)->params($this->params)->user($this->api_user)->send($this->add_params);

        if(!isset($result))
        {
            Logger::warn("no user");
        }else
        {
            Logger::info("User: ".$result->id_user);
        }
    }
}
