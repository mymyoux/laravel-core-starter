<?php

namespace Core\Services;


class IP
{
	
	public function __construct()
	{
		
	}
	protected function getRequestIP()
	{
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
          $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
          return $_SERVER["HTTP_CF_CONNECTING_IP"];
        }
		$ip =  request()->ip();
		//local v6
		if($ip == "::1")
		{
			return "127.0.0.1";
		}
		return $ip;
	}
	public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }
}
