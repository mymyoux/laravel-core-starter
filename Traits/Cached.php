<?php

namespace Core\Traits;

use Illuminate\Support\Facades\Cache;
trait Cached
{
	protected static $_cached_key;
	abstract protected function _getById($id);
	protected function getById($id)
	{
		$key = str_replace("%id", $id, static::$_cached_key);
		if(config('app.env') == 'local' && !config('app.local_cache'))
		{
			 $model = $this->_getById($id);
		}else {
			$model = Cache::get($key);
			if(!$model)
			{
				$model = $this->_getById($id);
				Cache::forever($key, $model);
			}
		}
        if(isset($model) && method_exists($this, "prepare"))
        {
        	$model = $this->prepare();
        }
        return $model;
	}
	protected function invalidate()
	{
		$key = str_replace("%id", $this->getKey(), static::$_cached_key);
        Cache::forget($key);
	}
	public static function bootCached()
	{
		$instance = (new static);
		if(isset($instance->cached_key))
		{
			static::$_cached_key = $instance->cached_key;
		}else
		{
			static::$_cached_key = $instance->getTable().":%id";
		}
	}
}
