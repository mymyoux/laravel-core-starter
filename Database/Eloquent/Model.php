<?php

namespace Core\Database\Eloquent;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Core\Database\Query\Builder as QueryBuilder;
abstract class Model extends BaseModel
{
  protected function newBaseQueryBuilder()
 {
    $connection = $this->getConnection();

    return new QueryBuilder(
        $connection, $connection->getQueryGrammar(), $connection->getPostProcessor()
    );
 }
}
