<?php

namespace Core\Queue\Jobs;

use Pheanstalk\Pheanstalk;
use Illuminate\Container\Container;
use Pheanstalk\Job as PheanstalkJob;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\BeanstalkdJob as BaseBeanstalkdJob;

class BeanstalkdJob extends BaseBeanstalkdJob
{
    public function release($delay = 0)
    {
    	$cls = $this->payload()["data"]["commandName"];
    	if(method_exists($cls, "getDelayRetry"))
    	{
    		$delay = $cls::getDelayRetry();
    	}
        return parent::release($delay);
    }
}
