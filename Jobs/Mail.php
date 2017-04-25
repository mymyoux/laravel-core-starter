<?php

namespace Core\Jobs;

use Core\Queue\JobHandler;
use Api as ApiService;
use Logger;
use Auth;
use Mail as MailService;
class Mail extends JobHandler
{
    protected $template;
    protected $variables;
    protected $message;
    protected $send_at;
    protected $ip_pool;
    protected $to;
    public $tries = 1;
    // public $supervisor = [
    //     "numprocs"=>8
    // ];
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    { 
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $result = MailService::_sendTemplateJob($this->template, $this->to, $this->variables, $this->message, $this->send_at,$this->ip_pool);

    }
}
