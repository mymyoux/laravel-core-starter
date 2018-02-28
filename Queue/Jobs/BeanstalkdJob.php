<?php

namespace Core\Queue\Jobs;

use Pheanstalk\Pheanstalk;
use Illuminate\Container\Container;
use Pheanstalk\Job as PheanstalkJob;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\BeanstalkdJob as BaseBeanstalkdJob;

class BeanstalkdJob extends BaseBeanstalkdJob
{
    protected $original_job;
    public function release($delay = 0)
    {
    	$delay = $delay || $this->getDelayRetry();
        return parent::release($delay);
    }
    public function getJobType()
    {
        return $this->getOriginalJob()->queue_type;
    }
    public function getOriginalJob()
    {
        if(!isset($this->original_job))
        {
            $command = $this->payload()["data"]['command'];
            $this->original_job = unserialize($command);
        }
        return $this->original_job;
    }
    public function payload()
    {
        //dd($this->getRawBody());
        return json_decode($this->getRawBody(), true);
    }
    public function getDelayRetry()
    {
    	$delay = 0;
    	$cls = $this->payload()["data"]["commandName"];
    	if(method_exists($cls, "getDelayRetry"))
    	{
    		$delay = $cls::getDelayRetry();
    	}
        if($delay == 0)
        {
            return 1;
        }
    	return $delay;
    }
}
