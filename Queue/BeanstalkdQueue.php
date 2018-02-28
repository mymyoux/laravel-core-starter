<?php

namespace Core\Queue;

use Pheanstalk\Pheanstalk;
use Pheanstalk\Job as PheanstalkJob;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\BeanstalkdQueue as BaseBeanstalkdQueue;
use Core\Queue\Jobs\BeanstalkdJob;

class BeanstalkdQueue extends BaseBeanstalkdQueue
{
    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        $job = $this->pheanstalk->watchOnly($queue)->reserve(0);

        if ($job instanceof PheanstalkJob) {
            return new BeanstalkdJob(
                $this->container, $this->pheanstalk, $job, $this->connectionName, $queue
            );
        }
    }
    protected function createObjectPayload($job)
    {
        $payload = parent::createObjectPayload($job);
        return $payload;
    }
}
