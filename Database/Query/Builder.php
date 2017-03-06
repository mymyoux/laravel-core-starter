<?php

namespace Core\Database\Query;

use Closure;
use RuntimeException;
use BadMethodCallException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Db;
class Builder extends BaseBuilder
{
     public function insert(array $values)
    {
        if(!isset($values["created_time"]))
        {
            $values["created_time"] = Db::raw('NOW(3)');
        }
        return parent::insert($values);
    }
     public function insertGetId(array $values, $sequence = null)
    {
       if(!isset($values["created_time"]))
		{
			$values["created_time"] = Db::raw('NOW(3)');
	    }
	    return parent::insertGetId($values, $sequence);
    }
}
