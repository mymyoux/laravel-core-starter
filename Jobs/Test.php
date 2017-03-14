<?php

namespace Core\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Core\Traits\Job;
use Core\Queue\JobHandler;
use App;
use Notification;
use Logger;
use Auth;
class Test extends JobHandler
{
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
        Logger::info("id_user:".Auth::id());
       
    }
}
