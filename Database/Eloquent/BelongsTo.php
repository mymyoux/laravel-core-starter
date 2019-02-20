<?php
namespace Core\Database\Eloquent;

use Illuminate\Database\Eloquent\Relations\BelongsTo as BaseBelongsTo;
class BelongsTo extends BaseBelongsTo
{
    public function getResults()
    {
        $model = $this->query->getModel();
        if(isset($model) && method_exists($model ,"isCached"))
        {
            if($model->getKeyName() == $this->foreignKey)
            {
                if(isset($this->child->{$this->ownerKey}))
                {
                    //if not in cache => create cache
                    // if(!$model->isCached($this->child->{$this->ownerKey}))
                    // {
                    //     return parent::getResults();
                    // }
                    $query = $this->query->applyScopes();
                    $wheres = $query->getQuery()->wheres;
                    foreach($wheres as $where)
                    {
                        if(!isset($where["type"]) || !isset($where["operator"]) || !isset($where["column"]) || !isset($where["value"]))
                        {
                            return parent::getResults();
                        }
                        if($where["type"] != 'Basic' || !in_array($where['operator'], ["=","!=","<>"]))
                        {
                            return  parent::getResults();
                        }
                        if(!starts_with($where["column"], $model->getTable()."."))
                        {
                            return parent::getResults();
                        }
                    }
                    
                    $key = $this->child->{$this->ownerKey};
                    $model = $model::find($key);
                    if ($model)
                    {
                        foreach($wheres as $where)
                        {
                            $column = substr($where["column"], strlen($model->getTable()."."));
                            if($where["operator"] == "=")
                            {
                                if($model->$column != $where["value"])
                                {
                                    return NULL;
                                }
                            }else
                            if($where["operator"] == "!=" || $where["operator"] == "<>")
                            {
                                if($model->$column == $where["value"])
                                {
                                    return NULL;
                                }
                            }
                        }
                    }
                    return $model;
                }
            }
            else
            {
                // manage key if model exist && in cache
                // IE: id_user in foreign for id (users)
                if (isset($this->child->{$this->foreignKey}))
                {
                    $query = $this->query->applyScopes();
                    $wheres = $query->getQuery()->wheres;
                    foreach($wheres as $where)
                    {
                        if(!isset($where["type"]) || !isset($where["operator"]) || !isset($where["column"]) || !isset($where["value"]))
                        {
                            return parent::getResults();
                        }
                        if($where["type"] != 'Basic' || !in_array($where['operator'], ["=","!=","<>"]))
                        {
                            return  parent::getResults();
                        }
                        if(!starts_with($where["column"], $model->getTable()."."))
                        {
                            return parent::getResults();
                        }
                    }
                    
                    $key = $this->child->{$this->foreignKey};
                    if ($key) {

                        $model = $model::find($key);
                        if ($model)
                        {
                            foreach($wheres as $where)
                            {
                                $column = substr($where["column"], strlen($model->getTable()."."));
                                if($where["operator"] == "=")
                                {
                                    if($model->$column != $where["value"])
                                    {
                                        return NULL;
                                    }
                                }else
                                if($where["operator"] == "!=" || $where["operator"] == "<>")
                                {
                                    if($model->$column == $where["value"])
                                    {
                                        return NULL;
                                    }
                                }
                            }
                        }
                        return $model;
                    }
                }
            }
        }

        return parent::getResults();
    }
}
