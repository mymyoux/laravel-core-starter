<?php

namespace Core\Queue;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Core\Traits\Job;
use App;
use Notification;
class JobHandler implements ShouldQueue
{
    use Job;
    public $tries = 3;
    public $timeout = 0;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }
    /**
     * Retry delay after a fail
     */
    public static function getDelayRetry()
    {
        return 5;
    }
    public function handle()
    {
       
    }
}
