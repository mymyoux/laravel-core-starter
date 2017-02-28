<?php
namespace Core\Api;
use Core\Exception\ApiException;
use Core\Exception\Exception;
use Illuminate\Http\Request;
use DB;
class Paginate
{
	const HAVING = "having";
	const WHERE = "where";
	const BOTH = "where/having";
	/**
	 * Server Request
	 */
	protected $request;
	public function __construct(Request $request)
	{
		$this->setRequest($request);
	}
	public function setRequest($request)
	{
		$this->request = $request;
	}
	public function apply($request, $mapping = NULL, $havingOnly = NULL)
	{
		$originalQuery = method_exists($request, "getQuery")?$request->getQuery():$request;

		$paginate = $this->request->input("paginate");
		//$request->limit($paginate["limit"]);

		$next= NULL;
		$previous = NULL;
		$keys = $paginate["key"];

		if(isset($paginate["next"]))
			$next = $paginate["next"];
		if(isset($paginate["previous"]))
			$previous = $paginate["previous"];
		$directions = $paginate["direction"];

		$limit = $paginate["limit"];



	    $columns = new ColumnsTester();
        $columns->parseQuery($originalQuery, $havingOnly);

		$has_group = !empty($originalQuery->groups);

        $havingCustom = [];
        $used = [];
        $useWhere = [];
        $has_having = False;

        // $selects = $originalQuery->columns;
        

     
        //mapping
        foreach($keys as $index=>$k)
        {
            if(isset($mapping))
            {
                if(is_array($mapping))
                {
                    if(isset($mapping[$k]))
                    {
                        $key = $mapping[$k];
                        if(strpos($key, ".") === False)
                        {
                            $force_having = True;
                        }
                    }else
                    {
                        $key = $mapping[0].".".$k;
                    }
                }else
                if(is_string($mapping))
                {
                    $key = $mapping.".".$k;
                }
                $keys[$index] = $key;
            }
            /*
            $use_where = False;
            $use_having = False;
            if(isset($having))
            {
            	 if(isset($having[$k]))
                 {
                 	if($having[$k] === True)
                 	{
                 		$use_having = True;
                 		if(strpos($keys[$index], ".")!==False)
                 		{
                 			$use_where = True;
                 		}
                 	}else
                 	{
                 		if(stripos($having[$k], "having") !== False)
                 		{
                 			$use_having = true;
                 		}
                 		if(stripos($having[$k], "where") !== False)
                 		{
                 			$use_where = true;
                 		}
                 		if(stripos($having[$k], "both") !== False)
                 		{
                 			$use_where = true;
                 			$use_having = true;
                 		}
                 	}
                 }else
                 {
                 	if($has_group)
                 	{
                 		$use_where = True;
                 		$use_having = True;
                 	}
                 }
            }
            if($use_having)
            {
            	$has_having = True;
            }
            $useWhere[$index] = $use_where === $use_having && $use_having? Paginate::BOTH:($use_having == True?Paginate::HAVING:Paginate::WHERE);
            */
        }
        $columns->parseParameters($keys);
        $columns->addSelectForHaving($originalQuery, $keys);



        //has having => detect which columns are having/where only

        //force to nest previous wheres
        if(!empty($originalQuery->wheres))
        {
	        $querynested = $originalQuery->newQuery();
	      	$querynested->wheres = $originalQuery->wheres;
	      	$originalQuery->wheres = [["type"=>"nested", "query"=>$querynested,"boolean"=>"and"]];
        }
        if(!empty($originalQuery->havings))
        {
	        $querynested = $originalQuery->newQuery();
	        $querynested->havingRaw("(", [], "");
	        $querynested->havingRaw(")", [], "");

	      	array_unshift($originalQuery->havings,  $querynested->havings[0]);
	      	$originalQuery->havings[] = $querynested->havings[1];
        }


        if(isset($keys) || isset($limit))
        {
        	$originalQuery->limit($limit);
        }


        $originalQuery->where(function($query) use($next, $keys, $useWhere, $directions, $limit, $previous, $columns){


         	if(isset($keys) || isset($limit))
       	 	{
	            if(isset($next) || isset($previous))
	            {
	            	$data = isset($next)?$next:$previous;

	                $first = True;
	                foreach($keys as $index=>$key)
	                {
	                	if(!$columns->isWhere($key))
	                	{
	                		continue;
	                	}
	                    $direction = $directions[$index];
	                    if(isset($next))
	                    {
	                    	$sign = $direction > 0 ?">":"<";
	                    }else
	                    {
	                    	$sign = $direction < 0 ?">":"<";
	                    }
						if(mb_strlen($data[$index]))
	                    {
	                    		$query = $query->where($key, $sign, $data[$index],  $first?'and':'or');
	                    }else
	                    {
	                    	if($first)
	                    	{
	                        	$query = $query->where(function($q) use($key, $data, $sign)
	                        	{
	                        		$q->orWhereNull($key)
	                        		->orWhere($key, $sign, $data[$index]);
	                        	});
	                    	}else
	                    	{
	                        	$query = $query->orWhere(function($q) use($key, $data, $sign)
	                        	{
	                        		$q->orWhereNull($key)
	                        		->orWhere($key, $sign, $data[$index]);
	                        	});

	                    	}
	                   	}
	                    for($i=0;$i<$index; $i++)
	                    {
	                    	if(!$columns->isWhere($keys[$i]))
		                	{
		                		continue;
		                	}
		                	 $direction = $directions[$i];
		                    if(isset($next))
		                    {
		                    	$sign = $direction > 0 ?">":"<";
		                    }else
		                    {
		                    	$sign = $direction < 0 ?">":"<";
		                    }

	                        if(mb_strlen($data[$i]))
	                        {
	                    		$query = $query->where($keys[$i], "=", $data[$i],'and');
	                        }else
	                        {
	                        	$query = $query->where(function($q) use($keys, $i, $data, $directions, $sign)
	                        	{
	                        		$q->whereNull($keys[$i])
	                        		->orWhere($keys[$i], $sign, $data[$i]);
	                        	});
	                        }
	                    }
	                    $first = False;
	                }
	            }
	        }
        });
		if($columns->hasHaving())
		{	
			$query = $originalQuery;
			//having hack
			$query->havingRaw("(", [], "and");
	        if(isset($keys) || isset($limit))
	        {
	            if(isset($next) || isset($previous))
	            {
	            	$data = isset($next)?$next:$previous;
	                $first = True;
	                foreach($keys as $index=>$key)
	                {
	                	if(!$columns->isHaving($key))
	                	{
	                		dd('not having');
	                		continue;
	                	}
	                	$direction = $directions[$index];
	                    if(isset($next))
	                    {
	                    	$sign = $direction > 0 ?">":"<";
	                    }else
	                    {
	                    	$sign = $direction < 0 ?">":"<";
	                    }
	                    if(mb_strlen($data[$index]))
                        {
                        	$query->having($key, $sign, $data[$index], $first?"":"or"/* $first?'and':'or'*/);
                        }else
                        {
                        	$query->havingRaw("(", [], $first?"":"or");
                    		$query->havingRaw("? IS NULL OR ? ".$sign." ?", [$key, $key, $data[$index]]);
                        	$query->havingRaw(")", [], "");
                        }
                    
	                    for($i=0;$i<$index; $i++)
	                    {
	                    	if(!$columns->isHaving($keys[$i]))
		                	{
		                		continue;
		                	}
		                	$direction = $directions[$i];
		                    if(isset($next))
		                    {
		                    	$sign = $direction > 0 ?">":"<";
		                    }else
		                    {
		                    	$sign = $direction < 0 ?">":"<";
		                    }
	                        if(mb_strlen($data[$i]))
	                        {
	                    		$query = $query->having($keys[$i], "=", $data[$i],'and');
	                        }else
	                        {
	                        	$query->havingRaw("(", [], "");
                        		$query->havingRaw("? IS NULL OR ? ".$sign." ?", [$keys[$i], $keys[$i], $data[$i]], "or");
	                        	$query->havingRaw(")", [], "");
	                        }
	                    }
	                    $first = False;
	                }
	            }
	        }
	        $query->havingRaw(")", [], "");
				
		}
		$sql = $originalQuery->toSql();
		$bindings = $query->getBindings();
	 	foreach( $bindings as $binding )
	 	{
            $sql = preg_replace("#\?#", "'".$binding."'", $sql, 1);
	 	}
		echo $sql;
        dd($originalQuery->get());
		exit();
        exit();
        if($this->hasHaving())
        {

            if(isset($this->direction))
            {
                if(!is_array($this->direction))
                {
                    $temp = $this->direction;
                    $this->direction = array_map(function($item) use($temp)
                    {
                        return $temp;
                    }, $this->key);
                }
                $direction = array_map(function($item)
                {
                    return $item>0?'ASC':'DESC';
                }, $this->direction);
            }
            $havingRequest = [];
            foreach($keys as $index=>$key)
            {
                if(!$used[$index])
                {
                    continue;
                }

                if(isset($havingCustom[$index]))
                {
                    $havingRequest[] = new expression($havingCustom[$index]." ".$direction[$index]);
                }else
                {
                    $havingRequest[$key] = $direction[$index];
                }
            }
            /*
            $new_having = NULL;
            if(isset($havingMapping) && !empty($havingMapping))
            {
                foreach($havingMapping as $havingM)
                {
                    if($havingM->match($this->key))
                    {
                        $new_having = $havingM;
                        break;
                    }
                }
                if(isset($new_having))
                {
                    $havingRequest[$new_having->getColumn()]=$new_having->getHaving();
                    $request->addColumns([$this->key=>new Expression("")]);
                }
            }*/
            /*
            if(isset($havingMapping))
            {
                if(is_array($havingMapping))
                {
                    if(isset($mapping[$this->key]))
                    {
                        $key = $mapping[$this->key];
                    }else
                    {
                        $key = $mapping[0].".".$this->key;
                    }
                }else
                if(is_string($mapping))
                {
                    $key = $mapping.".".$this->key;
                }

            }*/
            if(!empty($havingRequest))
                $request = $request->having($havingRequest);
        }
		return $request;
	}


}

use Tables;
class ColumnsTester
{
	const PATTERN = '/([^ ]+)( +(as)? +([^. ]+))?/';
	protected $whereColumns;
	protected $havingColumns;
	protected $tables;
	protected $needsHaving = False;
	protected $havingOnly;
	public function parseQuery($query, $havingOnly)
	{

		if(!empty($this->groups))
		{
			$this->needsHaving = True;
		}
		//get tables infos
		$tables = [$query->from];
		if(!empty($query->joins))
		{
			foreach($query->joins as $table)
			{
				$tables[] = $table->table;
			}
		}
		$tables = array_reduce($tables, function($previous, $item)
		{
			$previous[$item] = Tables::getColumns($item);
			return $previous;
		}, []);

		$this->tables = $tables;

		//where columns
		$this->whereColumns = [];
		foreach($tables as $key=>$value)
		{
			$this->whereColumns = array_merge($this->whereColumns, $value);
			$this->whereColumns = array_merge($this->whereColumns, array_map(function($item) use($key) {return $key.".".$item;},$value));
		}

		$this->havingColumns = [];

		$columns = $query->columns;
		if(empty($columns))
		{
			$columns = ["*"];
		}

		$havingColumns = [];
        $selectKeys = [];
        foreach($columns as $select)
        {
        	$havingColumns = array_merge($havingColumns, array_reduce(explode(",", $select), function($previous, $item)
    		{
    			$item = str_replace('‘', '', $item);
    			$item = trim($item);
    			$data = [];
    			preg_match(ColumnsTester::PATTERN, $item , $data);
    			$having = [$data[1]];
    			if(count($data)>2)
    			{
    				$having[] = $data[4];
    			}
    			return array_merge($previous, $having);
    		}, []));
		}
		foreach($havingColumns as $column)
		{
			if(($result = $this->getHavingColumns($column)) !== NULL)
			{
				$this->havingColumns = array_merge($this->havingColumns, $result);
			}else
			{
				$this->havingColumns[] = $column;
			}
		}
		$this->havingOnly = isset($havingOnly)?$havingOnly:[];
		$this->havingOnly = array_reduce($this->havingOnly, function($previous, $item)
		{
			$previous = array_merge($previous, $this->getHavingColumns($item));
			return $previous;
		}, []);
	}
	public function parseParameters($params)
	{
		foreach($params as $param)
		{
			if(!$this->isWhere($param))
			{
				$this->needsHaving = True;
				break;
			}
		}
	}
	public function addSelectForHaving($query, $params)
	{
		if(!$this->needsHaving)
		{
			//not needed
			return;
		}
		foreach($params as $param)
		{
			if(!$this->isHaving($param))
			{
				$columns = $this->getHavingColumns($param);
				$query->addSelect($columns[0]);
				$this->havingColumns = array_merge($this->havingColumns, $columns);
			}
		}
	}	
	public function isWhere($column)
	{
		//if no parsing or no having & not blacklisted
		return !$this->hasHaving($column) || !isset($this->whereColumns) || (in_array($column, $this->whereColumns) && !in_array($column, $this->havingOnly));
	}
	public function isHaving($column)
	{
		return in_array($column, $this->havingColumns);
	}
	public function hasHaving()
	{
		return $this->needsHaving;
	}
	protected function getHavingColumns($name)
	{
		//agreggate
		if(strpos($name, "(")!==False)
		{
			$this->needsHaving = True;
			return NULL;
		}
		$names = explode(".", $name);
		if(count($names) == 1)
		{
			$tables = array_keys($this->tables);
			$column = $names[0];
		}else
		{
			$tables = [$names[0]];
			$column = $names[1];
		}
		if($column == "*")
		{
			$columns = [];
			foreach($tables as $table)
			{
				$columns = array_merge($columns, $this->tables[$table]);
				$columns = array_merge($columns, array_map(function($item) use($table){return $table.".".$item;}, $this->tables[$table]));
			}
			return $columns;

		}
		foreach($tables as $table)
		{
			if(in_array($column, $this->tables[$table]) !== False)
			{
				return [$table.".".$column, $column];
			}
		}
		return NULL;
	}
}
