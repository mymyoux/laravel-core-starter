<?php

namespace Core\Queue;

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
class Crawl extends JobHandler
{
     public static function getDelayRetry()
    {
        return 0;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    protected function unserializeData($data)
    {
        $this->data = [];
        // $this->data["username"] = $data->bot_name ?? config('services.slack.username', 'robot');
        // $this->data["icon_emoji"] = $data->icon ?? config('services.slack.icon', ':deciduous_tree:');
        // $this->data["text"] = $data->message ?? '';
        // $this->data["channel"] = $data->channel ?? config('services.slack.channel', 'general');
        // $this->data["attachments"] = $data->attachments ?? NULL;
        // $allowed_env = config('services.slack.allowed_env', ['prod']);
        // if(!Notification::isAllowedEnv())
        // {
        //     $this->data["channel"] = config('services.slack.test_channel', 'random');
        // }
        // if(mb_strpos($this->data["channel"], '#') === false )
        // {
        //     $this->data["channel"] = "#".$this->data["channel"];
        // }
    }
    public function handle()
    {
        dd('a');
    }
}
