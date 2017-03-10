<?php

namespace Core\Services;

use Auth;
Use Job;

class Notification
{
	public function alert($type, $optional_message = '', $user = NULL)
    {
        $color = 'danger';

        switch ( $type )
        {
            case 'inbox_reply_authorize':
                $icon       = ':warning: :inbox_tray:';
                $message    = 'We need to authorize ';
                break;
             case 'beanstalkd':
                $icon       = ':fire: :fire_engine: ';
                $message    = 'Beanstalkd is DOWN ';
                break;
            case 'build_cv':
                $icon       = ':bomb: :building_construction:';
                $message    = 'BUILD CV error ';
                break;

            case 'podio_hooks_error':
                $icon       = ':warning:';
                $message    = 'Podio Webhooks Error ';
                break;
            case 'merge_tags':
                $icon       = ':warning:';
                $message    = 'Tag authorize ';
                $color      = 'warning';
                break;

            case 'node-https-cert':
                $icon       = ':key:';
                $message    = 'Certificat generation for node.yborder.com ';
                break;

            case 'candidate_temp':
                $icon       = ":skier:";
                $message    = "New alone candidate";
                break;
            case 'social_connect':
                $icon       = ":eyes:";
                $message    = "Connect by social network: ";
                break;
            case 'mail_action':
                $icon       = ":lemon:";
                $message    = "reply by mail: ";
                break;
            case 'unsubscribe_email':
                $icon       = ":cold_sweat:";
                $message    = "unsubscribe email: ";
                break;
            case 'techtalent':
                $icon       = ":video_game:";
                $message    = "register from techtalent: ";
                break;
            case 'detect_language':
                $icon = ':lips:';
                $message = " detect language: ";
                break;
            default:
                $icon       = ':warning:';
                $message    = 'Alert: ';
                break;
        }
        $text = "$icon\t $message$optional_message";
        if(isset($user))
        {
            $text.=PHP_EOL.$user->id." ".$user->first_name." ".$user->last_name;
        }

        return $this->sendNotification("alert", $text."\n");
    }

    public function sendNotification($channel, $message, $attachments = [], $bot_name = 'Robot', $icon = ':robot_face:')
    {
        $user = Auth::check() ? Auth::user() : NULL;

        // if(isset($user) && isset($user->type) && $user->type!="admin" && !$this->sm->get("AppConfig")->isLocal())
        // {
        //     try
        //     {
        //         if($channel != "errors")
        //             parent::sendNotification($user->type, $message, $attachments, $bot_name, $icon);
        //     }catch(\Exception $e)
        //     {

        //     }
        // }

        // $config = $this->sm->get("AppConfig")->get('slack');

        // if (isset($config["local_notifications"]["slack_channel"]))
        // {
        //     $channel = $config["local_notifications"]["slack_channel"];
        // }
        // else
        // {
        //     if(!$this->sm->get("AppConfig")->isProduction())
        //     {
        //         $channel = '#test_yb';
        //     }
        // }
        return Job::create('slack', $channel, $message, $attachments, $bot_name, $icon)->sendNow();

        // return parent::sendNotification($channel, $message, $attachments, $bot_name, $icon);
    }
}
