<?php

namespace Core\Database\Eloquent;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Core\Database\Query\Builder as QueryBuilder;

abstract class Model extends BaseModel
{
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    public function getDateFormat()
    {
        return 'Y-m-d H:i:s.u';
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

        if(isset($this->primaryKey) && !is_array($this->primaryKey) && isset($data[$this->primaryKey]))
            $data["id"] = $data[$this->primaryKey];

        return $data;
    }
}
