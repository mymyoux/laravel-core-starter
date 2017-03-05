<?php

namespace Core\Redis;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Redis\RedisManager as BaseRedisManager;

class RedisManager extends BaseRedisManager
{
    /**
     * Get the connector instance for the current driver.
     *
     * @return \Illuminate\Redis\Connectors\PhpRedisConnector|\Illuminate\Redis\Connectors\PredisConnector
     */
    protected function connector()
    {
        switch ($this->driver) {
            case 'phpredis':
                return new Connectors\PhpRedisConnector;
            default:
                return parent::connector();
        }
    }
}
