<?php

namespace Core\Redis\Connectors;

use Redis;
use RedisCluster;
use Illuminate\Support\Arr;
use Core\Redis\Connections\PhpRedisConnection;

use Illuminate\Redis\Connectors\PhpRedisConnector as BasePhpRedisConnector;

class PhpRedisConnector extends BasePhpRedisConnector
{
     /**
     * Create a new clustered Predis connection.
     *
     * @param  array  $config
     * @param  array  $options
     * @return \Illuminate\Redis\PhpRedisConnection
     */
    public function connect(array $config, array $options)
    {
        return new PhpRedisConnection($this->createClient(array_merge(
            $config, $options, Arr::pull($config, 'options', [])
        )));
    }
}
