<?php

namespace Core\Traits;

use Logger;
use Illuminate\Support\Facades\Cache;
trait CachedAuto
{
	protected static $_cached_key;
	protected function find($id)
	{
		$key = $this->getCacheKey();
		$model = Cache::get($key);
		if(!$model)
		{
			$model = parent::find($id);
			Cache::forever($key, $model);
		}
        if(isset($model) && method_exists($this, "prepareModel"))
        {
        	$model = $this->prepareModel($model);
        }
        return $model;
	}
	public function delete()
	{
		$this->invalidate();
		return parent::delete();
	}
	public function invalidate()
	{
		$key = $this->getCacheKey();
		Cache::forget($key);
		Logger::info("forget: ".$key);
	}
	public static function destroy($ids)
    {
		$ids = is_array($ids) ? $ids : func_get_args();
		foreach($ids as $id)
		{
			$instance = (new static);
			$instance->setKey($id);
			$instance->invalidate();
		}
		return parent::destroy($ids);
	}
	protected function getCacheKey()
	{
		return str_replace("%id", $this->getKey(), static::$_cached_key);
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
