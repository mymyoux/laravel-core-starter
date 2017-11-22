<?php

namespace Core\Database\Eloquent;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Core\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;
abstract class Model extends BaseModel
{
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';
    private $_prepared = False;
    
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }
    public function setRawAttributes(array $attributes, $sync = false)
    {
        parent::setRawAttributes($attributes, $sync);
        $this->prepare();
        return $this;
    }
    public function prepare()
    {
        if($this->_prepared)
            return;
        $this->_prepared = True;
        $this->prepareModel();
    }
    public function setKey($value)
    {
        return $this->setAttribute($this->getKeyName(), $value);
    }
    protected function prepareModel()
    {

    }
    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null)
    {
        $belongs = parent::belongsTo($related, $foreignKey, $ownerKey, $relation);
        $newbelongs = new BelongsTo($belongs->getQuery(), $belongs->getParent(), $belongs->getForeignKey(), $belongs->getOwnerKey(), $belongs->getRelation());

        return $newbelongs;
    }
    // public function hasOne($related, $foreignKey = null, $localKey = null)
    // {
    //     $relation = parent::hasOne($related, $foreignKey, $localKey);
    //     $relation = new HasOne($relation->getQuery(), $relation->getParent(), $relation->getForeignKey(), $relation->getLocalKey());

    //     return $relation;
	// }
    public function getDateFormat()
    {
        return 'Y-m-d H:i:s.u';
    }
    public function fromDateTime($value)
    {
        if($value instanceof Expression)
        {
            return $value;
        }
        return parent::fromDateTime($value);
    }

    public function asDateTime( $value )
    {
        if (!strpos($value, '.'))
        {
            $value .= '.000';
        }

        return parent::asDateTime($value);
    }

    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        return new QueryBuilder(
            $connection, $connection->getQueryGrammar(), $connection->getPostProcessor()
        );
    }

    public function toRawSQL()
    {
        return $this->query->toRawSql();
    }

    public function toArray()
    {
        $data = parent::toArray();

        if(isset($this->primaryKey) && !is_array($this->primaryKey) && isset($data[$this->primaryKey]) && !isset($data["id"]))
            $data["id"] = $data[$this->primaryKey];
        if(isset($this->pivot))
        {
            $d = $this->pivot->toArray();
            foreach($d as $key=>$value)
            {
                if(!isset($data[$key]))
                {
                    $data[$key] = $value;
                }
            }
        }
        return $data;
    }
}
