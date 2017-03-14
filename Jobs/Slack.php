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
class Slack extends JobHandler
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
    protected function unserializeData($data)
    {
        $this->data = [];
        $this->data["username"] = $data->bot_name ?? config('services.slack.username', 'robot');
        $this->data["icon_emoji"] = $data->icon ?? config('services.slack.icon', ':deciduous_tree:');
        $this->data["text"] = $data->message ?? '';
        $this->data["channel"] = $data->channel ?? config('services.slack.channel', 'general');
        $this->data["attachments"] = $data->attachments ?? NULL;
        $allowed_env = config('services.slack.allowed_env', ['prod']);
        if(!Notification::isAllowedEnv())
        {
            $this->data["channel"] = config('services.slack.test_channel', 'random');
        }
        if(mb_strpos($this->data["channel"], '#') === false )
        {
            $this->data["channel"] = "#".$this->data["channel"];
        }
    }
    public function handle()
    {
        Logger::info("id_user:".Auth::id());
        $slack  = config('services.slack');
        if(isset($slack))
        {
            $url    = $slack['url'];

            $ch = curl_init( $url );
            $json = json_encode($this->data);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json))
            );

            $result = curl_exec($ch);
            curl_close($ch);
        }

        $rocket  = config('services.rocket');
        if (isset($rocket))
        {
            try
            {
                $headers = array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($json),
                );
                foreach($rocket['headers'] as $key=>$header)
                {
                    $headers[] = $key.': '.$header;
                }
                $ch = curl_init( $rocket["url"] );

                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $result = curl_exec($ch);
                curl_close($ch);

            }
            catch(\Exception $e)
            {

            }
        }
        return $result;
    }
}
