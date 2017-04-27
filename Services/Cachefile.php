<?php

namespace Core\Services;

use Auth;
class Cachefile
{
    protected $cache;
	public function __construct()
	{
		
	}
	public function handle($path)
	{
        $prefix = "";
        if(starts_with($path, "/"))
        {
            $path = substr($path, 1);
            $prefix = "/";
        }
        if(starts_with($path, "js/"))
        {
            $path = substr($path, 3);
            $prefix .= "js/";
        }
        if(starts_with($path, "css/"))
        {
            $path = substr($path, 4);
            $prefix .= "css/";
        }
        if(!isset($this->cache))
        {
            $cachepath = storage_path('framework/cache/assets.php');
            if(file_exists($cachepath))
            {
                $this->cache = include $cachepath;
            }else {
                $this->cache = [];
            }
        }
        if(!isset($this->cache[$path]))
        {
            return $prefix.$path."?nocache=".microtime(true);
        }
        if(Auth::check() && Auth::user()->isAdmin())
        {
            return $prefix.$this->cache[$path]["map"]."?".$this->cache[$path]["suffix"];
        }
        return $prefix.$this->cache[$path]["min"]."?".$this->cache[$path]["suffix"];
	}
	public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }
}
