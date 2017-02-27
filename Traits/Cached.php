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
        $user = Cache::get($key);
        if(!$user)
        {
            $user = $this->_getById($id);
            Cache::forever($key, $user);
        }
        return $user;
	}
	protected function invalidate()
	{
		$key = str_replace("%id", $this->id_user, static::$_cached_key);
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
