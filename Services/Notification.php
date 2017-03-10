<?php

namespace Core\Services;

use Auth;
Use Job;

class Notification
{
    public function sendNotification($channel, $message, $attachments = [], $bot_name = 'Bot Name', $icon = ':robot_face:')
    {
        return Job::create('slack', $channel, $message, $attachments, $bot_name, $icon)->sendNow();
    }
}
