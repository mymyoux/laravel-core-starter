<?php

namespace Core\Database\Eloquent;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Core\Database\Query\Builder as QueryBuilder;
abstract class Model extends BaseModel
{
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

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
        if(isset($this->primaryKey) && isset($data[$this->primaryKey]))
            $data["id"] = $data[$this->primaryKey];
        return $data;
    }
}
