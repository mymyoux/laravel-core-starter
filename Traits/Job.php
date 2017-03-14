<?php

namespace Core\Traits;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Core\Traits\TraitJob;
use Core\Model\Beanstalkd;
trait Job
{
	use InteractsWithQueue, SerializesModels;
	public $queue;
	public $id;
	public $data;
    public $id_user;
    public $current_tries;

	public function __sleep()
    {
        return ["id"];
    }
    /**
     * Retry delay after a fail
     */
    public static function getDelayRetry()
    {
        return 5;
    }
    /**
     * Restore the model after serialization.
     *
     * @return void
     */
    public function __wakeup()
    {
    	$dbData = Beanstalkd::find($this->id);
    	$this->loadDbData($dbData);
    }
    public function loadDbData($dbData)
    {
        $this->data  = json_decode($dbData->json, False);
        $this->id_user = $dbData->id_user;
        $this->current_tries = $dbData->tries;
        //unserialization
        $this->unserializeData($this->data);
    }
    protected function unserializeData($data)
    {
    	if(!is_object($data))
    		return;
    	foreach($this->data as $key=>$value)
    	{
    		if(in_array($key, ["id","queue"]))
    			continue;

    		if(property_exists($this, $key))
    		{
    			$this->$key = $value;
    		}
    	}
    }
}
