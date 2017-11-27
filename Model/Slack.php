<?php
namespace Core\Model;
use Core\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Auth;
use Route;
use Core\Services\IP;
use App;
use Request;
use Illuminate\Console\Application;
use Core\Model\Slack\Attachment;
use Job;
class Slack extends \Tables\Model\Slack
{
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $table = 'slack';
	protected $primaryKey = 'id_slack';
    protected $fillable = [
        "id_user","channel","ts","original","external_id","external_type","answer_handler"
    ];

    public $text;
    public $username;
    public $thread_ts;
    public $mrkdwn;
    public $url;
    public $token;
    public $channel;
    public $response_type;
    public $replace_original;
    public $delete_original;
    public $message_ts;
    protected $attachments;
    public function removeAttachment($index = NULL)
    {
        if(!isset($index))
        {
            array_pop($this->attachments);
        }else
        array_splice($this->attachments, $index, 1);
        $this->attachments = array_values($this->attachments);
    }
     public function removeAttachments()
    {
        $this->attachments = NULL;
    }
    public function getAttachment($index)
    {
        return $this->attachments[$index];
    }
    public function getAttachments()
    {
        return $this->attachments;
    }
    public function setChannel($channel)
    {
        $this->slack_channel = $this->channel = $channel;
    }
    public function setText($text)
    {
        $this->text = $text;
    }   
    public function setUsername($username)
    {
        $this->username = $username;
    }
    public function setParent($parent)
    {
        $this->thread_ts = $parent;
    }
    public function setMarkdown($value = True)
    {
        $this->mrkdwn = $value;
    }
    public function setHandler($class, $method = "handle"){
        $this->answer_handler = $class."@".$method;
    }
    public function setResponseType($type = "in_channel")
    {
        $this->response_type = $type;
    }
    public function replaceOriginal()
    {
        $this->replace_original = True;
    }
    public function deleteOriginal()
    {
        $this->delete_original = True;
    }
    public function addAttachment($attachment, $index = NULL)
    {
        if(!isset($this->attachments))
        {
            $this->attachments = [];
        }
        if(isset($index))
        {
            array_splice($this->attachments, $index, 0, [$attachment]);
            $this->attachments = array_values($this->attachments);
        }else
        {
            $this->attachments[] = $attachment;
        }
    }
    public function toSlackJSON()
    {
        if(!isset($this->answer_handler))
        {
            throw new \Exception('you must specify an handler');
        }
        $keys = ["channel","text","username","mrkdwn","thread_ts","attachments","token","response_type","replace_original","delete_original"];
        $data = [];
        foreach($keys as $key)
        {
            if(isset($this->$key))
            {
                $data[$key] = $this->$key;
                if(is_numeric_array($this->$key))
                {
                    foreach($this->$key as $k=>$value)
                    {
                        if(method_exists($value, "toSlackJSON"))
                            $data[$key][$k] = $value->toSlackJSON();
                    }
                }
                if(method_exists($this->$key, "toSlackJSON"))
                {
                    $data[$key] = $this->$key->toSlackJSON();
                }
            }
        }
        if(isset($data["attachments"]))
        {
            $data["attachments"] = json_encode($data["attachments"]);
        }
        return $data;
    }
    public function exchangeArray($data)
    {
        foreach($data as $key=>$value)
        {
            $this->$key = $value;
        }
        if(isset($this->attachments))
        {
            if(is_string($this->attachments))
            {
                $this->attachments = json_decode($this->attachments);
            }
            $this->attachments = array_map(function($item)
            {
                $model = new Attachment();
                $model->exchangeArray($item);
                return $model;
            }, $this->attachments);
        }
    }
    public function loadFromDatabase()
    {
        $json = json_decode($this->original_request);
        return $this->exchangeArray($json);
    }
    public function send()
    {
       return Job::create(\Core\Jobs\SlackInteraction::class, ["slack"=>serialize($this)])->send();
    }
    public function sendNow()
    {
        $json = $this->toSlackJSON();
        if(isset($this->token))
        {
            if(isset($json->message_ts))
            {
                $url = "https://slack.com/api/chat.update";
            }else {
                $url = "https://slack.com/api/chat.postMessage";
            }
            $ch = curl_init($url);
            curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, 'POST');
            $builtJson = http_build_query($json);
            curl_setopt($ch, \CURLOPT_POSTFIELDS, $builtJson);
            curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($ch);
            curl_close($ch);
            try
            {
                $result = json_decode($result);
                $this->ts = $result->ts;
            }catch(\Exception $e)
            {
                dd($e);
            }
        }
        $this->slack_channel = $this->channel;

        if(Auth::check())
            $this->id_user = Auth::id();
       $this->saveAll();
       return $result;
    }
    public function save(array $options = [])
    {
        $json = $this->toSlackJSON();
        $this->original_request= json_encode($json);
        return parent::save($options);
    }
    public function saveAll()
    {
        $this->save();
        if(isset($this->attachments))
            foreach($this->attachments as $attachment)
            {
                $attachment->id_slack = $this->id_slack;
                $attachment->id_callback = $attachment->callback_id;
                $attachment->original_request = json_encode($attachment->toSlackJSON());
                $attachment->save();
            }
    }
    public function external()
    {
        return $this->morphTo();
    }
}
