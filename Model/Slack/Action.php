<?php
namespace Core\Model\Slack;
class Action
{

    public $name;
    public $text;
    public $type;
    public $value;
    public $style;
    public $data_source;
    public $min_query_length;


    protected $confirm;
    protected $options;
    protected $option_groups;
    protected $selected_options;

    public function setText($text)
    {
        $this->text = $text;
    }   

    public function setName($value)
    {
        $this->name = $value;
    }   

    public function setType($value)
    {
        if(!in_array($value, ["button","select"]))
        {
            throw new \Exception('action type must be button or select');
        }
        $this->type = $value;
    }   
    public function setValue($value)
    {
        $this->value = $value;
    }
    public function primary()
    {
        return $this->setStyle("primary");
    }
    public function danger()
    {
        return $this->setStyle("danger");
    }
    public function setStyle($value)
    {
        if(!in_array($value, ["default","primary","danger"]))
        {
            throw new \Exception('action style must be default, primary or danger');
        }
        $this->style = $value;
    }
    public function setConfirm($title, $text, $ok_text = "Okay", $cancel_text = "Cancel")
    {
        $this->confirm = ["title"=>$title, "text"=>$text,"ok_text"=>$ok_text, "dismiss_text"=>$cancel_text];
    }
    public function addOption($text, $value, $description = NULL)
    {
        if(!isset($this->options))
        {
            $this->options = [];
        }
        if(strlen($text)>30)
        {
            throw new \Exception("action's text must be up to 30 cars max");
        }
        if(strlen($description)>2000)
        {
            throw new \Exception("action's description must be up to 2000 cars max");
        }
        $option = ["text"=>$text, "value"=>$value];
        if(isset($description))
        {
            if(strlen($description)>30)
            {
                throw new \Exception("action's description must be up to 30 cars max");
            }
            $option["description"] = $description;
        }
        $this->options[] = $option;
    }
    public function addOptionGroup($category_text, $text, $value, $description = NULL)
    {
        if(!isset($this->option_groups))
        {
            $this->option_groups = [];
        }
        foreach($this->option_groups as $key=>$group)
        {
            if($group["text"] == $category_text)
            {
                $index = $key;
                break;
            }
        }
        if(!isset($index))
        {
            $index = count($this->option_groups);
            $this->option_groups[] = ["text"=>$category_text, "options"=>[]];
        }
        if(strlen($text)>30)
        {
            throw new \Exception("action's text must be up to 30 cars max");
        }
        if(strlen($description)>2000)
        {
            throw new \Exception("action's description must be up to 2000 cars max");
        }
        $option = ["text"=>$text, "value"=>$value];
        if(isset($description))
        {
            if(strlen($description)>30)
            {
                throw new \Exception("action's description must be up to 30 cars max");
            }
            $option["description"] = $description;
        }
        $this->option_groups[$index]["options"][] = $option;
    }
    public function setDataSource($value)
    {
        if(!in_array($value, ["static","users","channels","conversations","external"]))
        {
            throw new \Exception('action data source must be static, users, channels, conversations or external');
        }
        $this->style = $value;
    }
     public function exchangeArray($data)
    {
        foreach($data as $key=>$value)
        {
            $this->$key = $value;
        }
    }
    public function setSelectionOption($text, $value)
    {
        $this->selected_options = [["text"=>$text,"value"=>$value]];
    }
    public function toSlackJSON()
    {
        $keys = ["name","text","type","value","style","data_source","min_query_length","confirm","options","option_groups","selected_options"];
        $data = [];
        foreach($keys as $key)
        {
            if(isset($this->$key))
            {
                $data[$key] = $this->$key;
                if(method_exists($this->$key, "toSlackJSON"))
                {
                    $data[$key] = $this->$key->toSlackJSON();
                }
            }
        }
        if(!isset($data["name"]))
        {
            throw new \Exception('you must provide a name for each action');
        }
        if(!isset($data["type"]))
        {
            if(!empty($this->options) || !empty($this->option_groups))
            {
                $data["type"] = "select";
            }else {
                $data["type"] = "button";
            }
        }
        if(!isset($data["text"]))
        {
            throw new \Exception('you must provide a text for each action');
        }
        if(isset($data["min_query_length"]) && (!isset($data["data_source"]) || $data["data_source"]!=="external"))
        {
            throw new \Exception('min_query_length can\'t be used if data_source is not set to external');
        }
        if(isset($data["options"]) && isset($data["option_groups"]))
        {
            throw new \Exception('if option_groups is set, options is ignored');
        }
        return $data;
    }
}