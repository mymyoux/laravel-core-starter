<?php

namespace Core\Redis\Connections;

use Illuminate\Redis\Connections\PhpRedisConnection as BasePhpRedisConnection;
use Closure;

class PhpRedisConnection extends BasePhpRedisConnection
{
    protected $_data;
    public function __construct($client)
    {
        parent::__construct($client);
        $this->_data = [];
    }
    public function get($key)
    {
        if(array_key_exists($key, $this->_data))
        {
            return $this->_data[$key];
        }
        $result = parent::get($key);
        return $this->_data[$key] = $result;
    }
    
    public function set($key, $value, $expireResolution = null, $expireTTL = null, $flag = null)
    {
        $this->_data[$key] = $value;
        return parent::set($key, $value, $expireResolution, $expireTTL, $flag);
    }
    public function delete()
    {
        $keys = [];
        $len = func_num_args();
        for($i=0,$sum=0;$i<$len;$i++) {
                $key = func_get_arg($i);
                unset($this->_data[$key]);
                $keys[] = $key;
        }
        return $this->client->delete(...$keys);
    }
    public function del()
    {
        $keys = [];
        $len = func_num_args();
        for($i=0,$sum=0;$i<$len;$i++) {
                $key = func_get_arg($i);
                unset($this->_data[$key]);
                $keys[] = $key;
        }
        return $this->client->del(...$keys);
    }
}
