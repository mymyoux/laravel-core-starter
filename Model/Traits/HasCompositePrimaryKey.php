<?php
namespace Core\Model\Traits;

use Exception;
use Illuminate\Database\Eloquent\Builder;

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
        $me = new self;
        $query = $me->newQuery();
        foreach ($me->getKeyName() as $key) {
            $query->where($key, '=', isset($ids[$key]) ? $ids[$key] : $ids[$i]);
            $i++;
        }
        return $query->first($columns);
    }
}