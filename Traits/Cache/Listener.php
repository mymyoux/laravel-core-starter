<?php

namespace Core\Traits\Cache;
use Core\Traits\Cache as CacheTrait;
use Illuminate\Support\Facades\Cache as CacheService;
class Listener
{
    public function deleted($event,$model)
    {
        array_map([$this, "onDeleted"], $model);
    }
     protected function onDeleted($model)
    {
        if(!method_exists($model, "cacheDelete"))
        {
            return;
        }
        $model->cacheDelete();
    }
	public function saved($event,$models)
    {
        array_map([$this, "onSaved"], $models);
    }
    protected function onSaved($model)
    {
        if(!method_exists($model, "cacheSave"))
        {
            return;
        }
        $model->cacheSave();
    }
}
