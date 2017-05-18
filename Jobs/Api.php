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
    public $tries = 1;
    public $supervisor = [
        "numprocs"=>8
    ];
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
        $result = ApiService::path($this->path)->method($this->method)->params($this->params)->user($this->api_user)->send($this->add_params);

        // if(isset($result) && $this->output->isVerbose())
        //     var_dump($result);
    }
}
