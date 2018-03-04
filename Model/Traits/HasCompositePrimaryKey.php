<?php
namespace Core\Model\Traits;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

trait HasCompositePrimaryKey
{
   /**
    * Get the value indicating whether the IDs are incrementing.
    *
    * @return bool
    */
   public function getIncrementing()
   {
       return false;
   }
   /**
    * Set the keys for a save update query.
    *
    * @param  \Illuminate\Database\Eloquent\Builder $query
    * @return \Illuminate\Database\Eloquent\Builder
    */
   protected function setKeysForSaveQuery(Builder $query)
   {
       foreach ($this->getKeyName() as $key) {
           if (isset($this->$key))
               $query->where($key, '=', $this->$key);
           else
               throw new Exception(__METHOD__ . 'Missing part of the primary key: ' . $key);
       }
       return $query;
   }
   
    public function getKey()
    {
        $data = [];
        
        foreach ($this->getKeyName() as $key) {
            $data[ $key ] = $this->getAttribute($key);
        }
        
        return $data;
    }

    protected function findComposite($ids, $columns = ['*'])
    {
        $i = 0;
        $me = (new static);
        $query = $me->newQuery();
        foreach ($me->getKeyName() as $key) {
            $query->where($key, '=', isset($ids[$key]) ? $ids[$key] : $ids[$i]);
            $i++;
        }

        if (class_use_trait($this, 'Core\Traits\CachedAuto'))
        {
            $key = $this->getCacheKey($ids);
            $model = Cache::get($key);

            if(!$model) 
            {
                $model = $query->first($columns);

                if ($model !== null)
                {
                    $model->cache();
                }
            }
            else
            {
                $model->dishandleCache();
                if(method_exists($model, "afterCache"))
                {
                    $model->afterCache();
                }
                $model->from_cache = true;
            }
            if(isset($model) && method_exists($model, "prepare"))
            {
                $model->prepare();
            }
            return $model;
        }

        $model = $query->first($columns);

        return $model;
    }
}