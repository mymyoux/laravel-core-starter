<?php

namespace Core\Traits;

use Core\Traits\Cache\Serialized;
use Logger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
trait CachedAuto
{
	protected static $_cached_key;
	protected function find($id, $columns = ['*'])
	{
		if(is_array($id))
		{
			return array_map(function($id){
				return $this->find($id, $columns);
			}, $id);
		}
		$key = $this->getCacheKey($id);
		$model = Cache::get($key);
		if(!$model)
		{
			$model = parent::find($id, $columns);
			$model->cache();
		}
		else
		{
			$this->hydrateObject($model);
			if(method_exists($model, "afterCache"))
			{
				$model->afterCache();
			}
		}
        if(isset($model) && method_exists($model, "prepareModel"))
        {
        	$model->prepareModel($model);
        }
        return $model;
	}
	public function cache()
	{
		if(strpos(get_class($this), "CvModel")!==False)
		{
			$this;
		}
		if(method_exists($this, "beforeCache"))
		{
			$this->beforeCache();
		}
		$object = clone $this;
		$object->handleCache();
		$key = $object->getCacheKey();
		Cache::forever($key, $object);
	}
	public function handleCache()
	{
		$relations = $this->getRelations();
		foreach($relations as $key=>$relation)
		{
			$this->relations[$key] = $this->deshydrateObject($relation);
		}
		$attributes = $this->getAttributes();
		foreach($attributes as $key=>$attribute)
		{
			$this->attributes[$key] = $this->deshydrateObject($attribute);
		}
	}
	public function hydrateObject($data)
	{
		if(!isset($data) || (!is_object($data) && !is_array($data)))
		return $data;
		
		if($data instanceof Collection)
		{
			$data = $data->map(function(&$item)
			{
				return $this->hydrateObject($item);
			});
		}elseif(is_array($data))
		{
			$data = array_map(function(&$item)
			{
				return $this->hydrateObject($item);
			}, $data);
		}else
		{
			if($data instanceof Serialized)
			{
				$tmp = $data->cls::find($data->id);
				if(isset($data->relations))
				{
					$tmp->relations = $data->relations;
				}
				$data = $tmp;
			}

			if($data instanceof Model)
			{
				$relations = $data->getRelations();
				foreach($relations as $key=>$relation)
				{
					$data->relations[$key] = $this->hydrateObject($relation);
				}
			}
		}
		return $data;
	}
	protected function deshydrateObject(&$data)
	{
		if(!isset($data) || (!is_object($data) && !is_array($data)))
			return $data;
		
		if($data instanceof Collection)
		{
			$data = $data->map(function(&$item)
			{
				return $this->deshydrateObject($item);
			});
		}elseif(is_array($data))
		{
			$data = array_map(function(&$item)
			{
				return $this->deshydrateObject($item);
			}, $data);
		}else
		{
			
			$classes = class_uses($data);
			if($data instanceof Model)
			{
				$relations = $data->getRelations();
				foreach($relations as $key=>$relation)
				{
					
					$data->relations[$key] = $this->deshydrateObject($relation);
				}
			}
			if(isset($classes[CachedAuto::class]))
			{
				$tmp = new Serialized();
				$tmp->id = $data->getKey();
				$tmp->cls = get_class($data);
				if($data instanceof Model)
				{
					$relations = $data->getRelations();
					if(!empty($relations))
						$tmp->relations = $relations;
				}
				$data = $tmp;
			}
		}
		return $data;
	}
	public function save(array $options = [])
	{
		$this->invalidate();
		return parent::save($options);
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
	
	protected function getCacheKey($id = NULL)
	{
		return str_replace("%id", isset($id)?$id:$this->getKey(), static::$_cached_key);
	}
	public static function bootCachedAuto()
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
	public function __clone()
	{
		if(strpos(get_class($this), "CvModel")!==False)
		{
			$this;
		}
		$this->relations = $this->___clone($this->relations);
		$this->attributes = $this->___clone($this->attributes);
	}
	protected function ___clone(&$data)
	{
		if(strpos(get_class($this), "CvModel")!==False)
		{
			$this;
		}
		if($data instanceof Collection)
		{
			return $data->map(function($item){return $this->___clone($item);});
		}
		if(is_object($data))
			return clone $data;
		if(is_array($data))
			return array_map(function($item){return $this->___clone($item);}, $data);
		return $data;
	}
}
