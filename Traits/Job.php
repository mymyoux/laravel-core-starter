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

	public function __sleep()
    {
        return ["id"];
    }

    /**
     * Restore the model after serialization.
     *
     * @return void
     */
    public function __wakeup()
    {
    	$dbData = Beanstalkd::find($this->id);
    	$this->data  = json_decode($dbData->json, False);

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
