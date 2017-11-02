<?php
namespace Core\Model\Slack;


use Core\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Core\Model\Slack as SlackModel;

class Attachment extends \Tables\Model\Slack\Attachment
{

    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $table = 'slack_attachment';
	protected $primaryKey = 'id_slack_attachment';
    protected $fillable = [
        "id_slack","id_callback","external_id","external_type","original_request"
    ];

    public $author_name;
    public $author_link;
    public $author_icon;

    public $color;
    public $fallback;
    public $pretext;
    public $text;

    public $title;
    public $title_link;

    public $image_url;
    public $thumb_url;

    public $footer;
    public $footer_icon;

    public $ts;

    public $callback_id;
    public $mrkdwn_in;


    protected $actions;

    public function setAuthor($name, $link = NULL, $icon = NULL)
    {
        $this->author_name = $name;
        if(isset($link))
            $this->author_link = $link;
        if(isset($icon))
            $this->author_icon = $icon;
    }
    public function setColor($color)
    {
        $this->color = $color;
    }   
    public function setCallbackId($id)
    {
        $this->callback_id = $id;
    }   
    public function setFallback($fallback)
    {
        $this->fallback = $fallback;
    }   
    public function setFooter($footer, $icon = NULL)
    {
         $this->footer = $footer;
        if(isset($icon))
            $this->footer_icon = $icon;
    }   
    public function setImage($image, $thumbnail = NULL)
    {
         $this->image_url = $image;
        if(isset($thumbnail))
            $this->thumb_url = $thumbnail;
    }
    public function setPretext($pretext)
    {
        $this->pretext = $pretext;
    }   
    public function setText($text)
    {
        $this->text = $text;
    }   
    public function setTitle($title, $link = NULL)
    {
        $this->title = $title;
        if(isset($link))
            $this->title_link = $link;
    }   
    public function setTimestamp($timestamp)
    {
        $this->ts = $timestamp;
    }
    public function addAction($action)
    {
        if(!isset($this->actions))
        {
            $this->actions = [];
        }
        $this->actions[] = $action;
    }
    public function setMarkdown($text = True, $pretext = True)
    {
        $this->mrkdwn_in = [];
        if($text)
            $this->mrkdwn_in[] = "text";
        if($pretext)
            $this->mrkdwn_in[] = "pretext";
    }
     public function exchangeArray($data)
    {
         foreach($data as $key=>$value)
        {
            $this->$key = $value;
        }
         if(isset($this->actions))
        {
            $this->actions = array_map(function($item)
            {
                $model = new Action();
                $model->exchangeArray($item);
                return $model;
            }, $this->actions);
        }
    }
    public function slack()
    {
        return $this->belongsTo(SlackModel::class,"id_slack","id_slack");
    }
    public function toSlackJSON()
    {
        $keys = ["mrkdwn_in","author_name","author_link","author_icon","color","fallback","pretext","text","title","title_link","image_url","thumb_url","footer","footer_icon","ts","callback_id","actions"];
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
        if(!empty($this->actions) && !isset($this->callback_id))
        {
            throw new \Exception('You set up actions so you must give a callback id');
        }
        return $data;
    }
}