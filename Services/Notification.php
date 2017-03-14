<?php

namespace Core\Services;

use Auth;
Use Job;
use App;
use Core\Jobs\Slack;
class Notification
{
    public function sendNotification($channel, $message, $attachments = [], $bot_name = 'Bot Name', $icon = ':robot_face:')
    {
        return Job::create(Slack::class, 
            ["channel"=>$channel, 
            "message"=>$message, 
            "attachments"=>$attachments, 
            "bot_name"=>$bot_name, 
            "icon"=>$icon])->send();
    }
    /**
     * Environment allowed to send in all channels on slack
     * @return boolean [description]
     */
    public function isAllowedEnv()
    {
        $allowed_env = config('services.slack.allowed_env', ['prod']);
        return in_array(App::environment(), $allowed_env);
    }
}
