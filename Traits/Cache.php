<?php

namespace Core\Traits;
use Illuminate\Support\Facades\Cache as CacheService;


trait Cache 
{
	private function _getCaching()
	{
		if(isset($this->caching))
		{
			$caching = $this->caching;
		}else {
			if(is_array($this->primaryKey))
			{
				$caching = $this->primaryKey;
			}else
			$caching = [$this->primaryKey];
		}
		sort($caching);
		return $caching;
	}
	public function cacheSave()
	{
		$caching = $this->_getCaching();
		
		$key = $this->table.":".join(array_map(function($item){return substr($item, 0, 1).$this->$item;}, $caching),"");
		CacheService::forever($key, $this);
	}
	public function cacheDelete()
	{
		if(isset($this->caching))
		{
			$caching = $this->caching;
		}else {
			$caching = [$this->primaryKey];
		}
		$key = $this->table.":".join(array_map(function($item){return substr($item, 0, 1).$this->$item;}, $caching),"");
		CacheService::forget($key, $this);
	}
	protected function cacheGet($request)
	{
		$caching = $this->_getCaching();
		if(!is_array($request) && count($caching)==1)
		{
			$request = [$caching[0]=>$request];
		}
		$key = $this->table.":".join(array_map(function($item) use($request) {return substr($item, 0, 1).$request[$item];}, $caching),"");
		
		$result = CacheService::get($key);
		if(!$result)
		{
			$result = static::where($request)->first();
			if(!$result)
				return $result;
			if(method_exists($result, "cacheSave"));
				$result->cacheSave();
			return $result;

		}
		return $result;
	}
	protected function cacheInvalidate()
	{	
		$prefix = config("cache.prefix")?config("cache.prefix").":":"";
		$keys = CacheService::connection()->getKeys($prefix.$this->table.":*");
		foreach($keys as $key)
		{
			CacheService::forget(substr($key, strlen($prefix)));
		}
	}
}
