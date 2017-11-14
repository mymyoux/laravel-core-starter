<?php

namespace Core\Services;
use Job;
use Weblee\Mandrill\Mail as MailMandrill;
use App\User;
use Core\Model\Mail as MailModel;
use Logger;
use Auth;
use Exception;

use Core\Model\Mandrill\Template as MandrillTemplateModel;


class Mail
{
	protected $mandrill;
	public function __construct(MailMandrill $mandrill)
	{
        $this->mandrill = $mandrill;
    }

    public function setKey( $key )
    {
        $this->mandrill = new MailMandrill($key);
    }

    public function getMandrill()
    {
        return $this->mandrill;
    }
    static public function template($template, $to, $data = NULL, $config = NULL, $send_at = NULL, $ip_pool = NULL)
    {
        $data = $data??[];
        $message = $config??[];

        if ($to instanceof \App\User)
        {
            if ($to->hasPlace())
            {
                $id_place = $to->getPlaceID();
                $language = null;

                switch ($id_place)
                {
                    case 193:
                        $language = 'fr';
                    break;
                }

                if (null !== $language)
                {
                    $template_exist = MandrillTemplateModel::getTemplate( $template, $language );

                    if (null !== $template_exist)
                    {
                        $template = $template_exist->slug;
                    }
                }
                else
                {
                    $language = 'en';
                }

                if (isset($message))
                {
                    if (!isset($message['tags']))
                    {
                        $message['tags'] = [];
                    }

                    $message['tags'][] = 'lang-' . $language;
                }
            }
        }

        return Job::create(\Core\Jobs\Mail::class, ["template"=>$template,"to"=>$to,"variables"=>$data,"message"=>$message,"send_at"=>$send_at,"ip_pool"=>$ip_pool])->send();
    }
    public function forbidden($template)
    {
        return NULL;
    }
    public function isAllowed($template, $id_user, $role_email = NULL)
    {
        $user =   is_numeric($id_user)? User::find($id_user):$id_user;
        if(!isset($user))
        {
            Logger::warn($id_user. ' no email because deleted');
            //deleted
            return False;
        }
        $id_user = $user->getKey();
        //delete
        if($user->deleted == 1)
        {
            Logger::warn($id_user . ' no email because deleted');
            return False;
        }
        if($user->hasRole("no_email"))
        {
            Logger::warn($id_user . ' no email because role: no_email');
            return False;
        }
        if(isset($role_email) && $user->hasRole($role_email))
        {
            Logger::warn($id_user . ' no email because role: '.$role_email);
            return False;
        }
        
        return True;
    }
    public function _sendTemplateJob($template, $to, $data, $message, $send_at, $ip_pool)
    {
        if(!isset($to) || (is_array($to) && empty($to)))
        {
            throw new Exception('no recipient for email');
        }

        if(!is_array($to))
        {
            $to = [$to];
        }
        
        $to = array_map(function($item){ $item["email"] = clean_email($item["email"]); return $item;},array_values(array_filter(array_map(function($recipient)
        {
            
            if(is_numeric($recipient))
            {
                $user = User::find($recipient);
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
            
            if(is_object($recipient))
            {
                return
                [
                    "email"=>$recipient->email,
                    "name"=>$recipient->login??$recipient->first_name,
                    "type"=>"to",
                    "id_user"=>$recipient->id_user
                ];
            }

            return NULL;
        }, $to), function($item)
        {
            return isset($item);
        })));
        $role_email = $this->forbidden( $template);

        //remove non allowed user (deleted, no_email etc)
        $to = array_values(array_filter($to, function($item)use($role_email, $template)
        {
            if(!isset($item["id_user"]))
            {
                return true;
            }
            return $this->isAllowed($template, $item["id_user"], $role_email);
          
        }));

        if(empty($to))
        {
            Logger::error("no recipient left");
            return;
            //throw new Exception('no valid recipient for email');
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
        
        if (!is_object($message))
        {
            $message = (object) $message;
        }

        if(!isset($message->merge_language))
            $message->merge_language = 'handlebars';

        if (empty($message->from_mail))
            $message->from_email  = config('services.mandrill.from_email');

        if (empty($message->from_name))
            $message->from_name   = config('services.mandrill.from_name');

        $message->to = array_map(function($item)
        {
            if(isset($item["id_user"]))
            {
                unset($item["id_user"]);
            }
            return $item;
        },$to);

        $data = (array) $data;

        foreach ($data as $key => $value)
        {
            $data[$key] = [
                'name'  => $key,
                'content'   => $value
            ];
        }
        
        $data = array_values($data);
        $message->global_merge_vars = $data;
        $result = $this->mandrill->messages()->sendTemplate($template, $data, $message, true, $send_at, $ip_pool);

        foreach($result as $key=>$resultemail)
        {

            
            $mail = new MailModel;
            $mail->type = $template;
            $mail->from = Auth::id();
            if(isset($message->subject))
                $mail->subject = $message->subject;
            $mail->recipient = $resultemail["email"];
            if(isset($message->from_email))
                $mail->sender = $message->from_email;
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
