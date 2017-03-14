<?php

namespace Core\Queue\Connectors;

use Pheanstalk\Pheanstalk;
use Illuminate\Support\Arr;
use Pheanstalk\PheanstalkInterface;
use Core\Queue\BeanstalkdQueue;
use Illuminate\Queue\Connectors\BeanstalkdConnector as BaseBeanstalkdConnector;

class BeanstalkdConnector extends BaseBeanstalkdConnector
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $retryAfter = Arr::get($config, 'retry_after', Pheanstalk::DEFAULT_TTR);

        return new BeanstalkdQueue($this->pheanstalk($config), $config['queue'], $retryAfter);
    }
}
