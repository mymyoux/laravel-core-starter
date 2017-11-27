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
use Illuminate\Foundation\Application;
class SlackInteraction extends JobHandler
{
    protected $slack;
     public static function getDelayRetry()
    {
        return 1;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    protected function unserializeData($data)
    {
        $this->slack = unserialize($data->slack);
    }
    public function handle()
    {
        $result = $this->slack->sendNow();
        Logger::info("id:".$result->ts);
    }
}
