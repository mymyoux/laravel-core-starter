<?php

namespace Core\Services;
use Job;
use Weblee\Mandrill\Mail as MailMandrill;
use App\User;
use Core\Model\Mail as MailModel;
use Logger;
use Auth;
class Mail
{
	protected $mandrill;
	public function __construct(MailMandrill $mandrill)
	{
        $this->mandrill = $mandrill;
	}
    public function template($template, $to, $data = NULL, $config = NULL, $send_at = NULL, $ip_pool = NULL)
    {
        $data = $data??[];
        $message = $config??[];
        return Job::create(\Core\Jobs\Mail::class, ["template"=>$template,"to"=>$to,"variables"=>$data,"message"=>$message,"send_at"=>$send_at,"ip_pool"=>$ip_pool]);
    }
    public function _sendTemplateJob($template, $to, $data, $message, $send_at, $ip_pool)
    {
        if(!isset($to) || (is_array($to) && empty($to)))
        {
            throw Exception('no recipient for email');
        }
        if(!is_array($to))
        {
            $to = [$to];
        }
        $to = array_map(function($item){ $item["email"] = clean_email($item["email"]); return $item;},array_values(array_filter(array_map(function($recipient)
        {
            if(is_numeric($recipient))
            {
                $user = User::getById($recipient);
                if(!isset($user))
                    return NULL;
                return
                [
                    "email"=>$user->email,
                    "name"=>$user->login??$user->first_name,
                    "type"=>"to",
                    "id_user"=>$user->id_user
                ];
            }
            if(is_string($recipient))
            {
                $user = User::getByEmail($recipient);
                if(!isset($user))
                    return [
                        "email"=>$recipient,
                        "type"=>"to"
                    ];

                return 
                [
                    "email"=>$recipient,
                    "name"=>$user->login??$user->first_name,
                    "type"=>"to",
                    "id_user"=>$user->id_user
                ];
            }
            if(is_array($recipient))
            {
                if(!isset($recipient["email"]))
                {
                    return NULL;
                }
                if(!isset($recipient["type"]))
                {
                    $recipient["type"] = "to";
                }
                return $recipient;
            }
            return NULL;
        }, $to), function($item)
        {
            return isset($item);
        })));
        if(empty($to))
        {
            throw Exception('no valid recipient for email');
        }
        if(config('services.mandrill.test') === true)
        {
            if(config('services.mandrill.email') !== NULL)
            {
                $to = array_map(function($recipient)
                {
                    $recipient['email'] = config('services.mandrill.email');
                    return $recipient;
                }, $to);
            }else {
                return Logger::info('Email test mode on - sending fake email '.$template.' to '.join(", ",array_map(function($item){return $item["email"];},$to)));
            }
        }
        if(!isset($message['merge_language']))
            $message['merge_language'] = 'handlebars';

        $message['to'] = array_map(function($item)
        {
            if(isset($item["id_user"]))
            {
                unset($item["id_user"]);
            }
            return $item;
        },$to);

        $result = $this->mandrill->messages()->sendTemplate($template, $data, $message, True,$send_at, $ip_pool);
        foreach($result as $key=>$resultemail)
        {
            $mail = new MailModel;
            $mail->type = $template;
            $mail->from = Auth::id();
            if(isset($message["subject"]))
                $mail->subject = $message['subject'];
            $mail->recipient = $resultemail["email"];
            if(isset($message["from_email"]))
                $mail->sender = $message['from_email'];
            $mail->message = json_encode($message);
            //TODO:maybe result is not in the same order => check email inside $to
            if($key<count($to))
            {
                if(isset( $to[$key]["id_user"]))
                {
                    $mail->id_user = $to[$key]["id_user"];
                }
            }
            $mail->status = $resultemail["status"];
            if(isset($resultemail["reject_reason"]))
            {
                $mail->reason = $resultemail["reject_reason"];
            }
            $mail->id_mandrill = $resultemail["_id"];
            $mail->save();
        }
        return $result;
    }
}
