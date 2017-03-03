<?php
namespace Core\Api;
use Core\Exception\ApiException;
use Core\Exception\Exception;
use Illuminate\Http\Request;
use DB;
use Api;
class Paginate
{
	const HAVING = "having";
	const WHERE = "where";
	const BOTH = "where/having";
	/**
	 * Server Request
	 */
	protected $request;
	protected $next;
	protected $previous;
	protected $directions;
	protected $keys;

	public function __construct(Request $request)
	{
		$this->setRequest($request);
	}
	public function setRequest($request)
	{
		$this->request = $request;
	}
	public function onResults($data)
	{
		$apidata = [];
		$apidata["count"] = count($data);
		$keys = $this->keys;
		if(isset($this->next))
		{
	         $data = array_values(array_filter($data, function($item) use($keys)
            {
                foreach($keys as $index=>$key)
                {

                    $direction = $this->directions[$index];
                    if($direction>0)
                    {
                        if($item->$key<=$this->next[$index])
                        {
                            continue;
                        }
                       // $where = $where->greaterThan($key, $this->next[$index]);
                    }else
                    {
                        if($item->$key>=$this->next[$index])
                        {
                            continue;
                        }
                        //$where = $where->lessThan($key, $this->next[$index]);
                    }

                    for($i=0;$i<$index; $i++)
                    {
                        if($item->{$keys[$i]}!=$this->next[$i])
                        {
                            continue 2;
                        }
                        //$where = $where->and;
                        //$where = $where->equalTo($keys[$i], $this->next[$i]);
                    }
                   return true;
                }
                return false;
            }));
		}
		 if(isset($this->previous))
        {
            $data = array_values(array_filter($data, function($item) use($keys)
            {
                foreach($keys as $index=>$key)
                {
                     $direction = $this->direction[$index];
                    if($direction<0)
                    {
                        if($item->$key<=$this->previous[$index])
                        {
                            continue;
                        }
                    }else
                    {
                        if($item->$key>=$this->previous[$index])
                        {
                            continue;
                        }
                    }

                    for($i=0;$i<$index; $i++)
                    {
                        if($item->{$keys[$i]}!=$this->previous[$i])
                        {
                            continue 2;
                        }
                    }
                    return true;
                }
                return false;
            }));
        }

        if(!empty($data))
        {
            $previous = [];
            foreach($this->keys as $key)
            {
                if(isset($data[0]->{$key}))
                {
                    $previous[] = $data[0]->{$key};
                }
            }
            $next = [];
            $len = sizeof($data)-1;
        
            foreach($this->keys as $key)
            {
                if(isset($data[$len]->{$key}))
                {
                    $next[] = $data[$len]->{$key};
                }
            }
            $apidata["next"] = $next;
            $apidata["previous"] = $previous;
        }

		Api::addApiData(["paginate"=>$apidata]);
	}
	public function apply($request, $mapping = NULL, $havingOnly = NULL)
	{
		$originalQuery = method_exists($request, "getQuery")?$request->getQuery():$request;

		if(!isset($originalQuery->processor) || !method_exists($originalQuery->processor, "setSelectListener"))
		{
			$originalQuery->processor = new \Core\Api\Paginate\Processor();
			
		}
		$originalQuery->processor->setSelectListener($this);

		$paginate = $this->request->input("paginate");
		//$request->limit($paginate["limit"]);

		$next= NULL;
		$previous = NULL;
		$this->keys = $keys = $paginate["keys"];

		if(isset($paginate["next"]))
			$this->next = $next = $paginate["next"];
		if(isset($paginate["previous"]))
			$this->previous = $previous = $paginate["previous"];
		$this->directions = $directions = $paginate["directions"];

		$limit = $paginate["limit"];




	    $columns = new ColumnsTester();
        $columns->parseQuery($originalQuery, $havingOnly);

		$has_group = !empty($originalQuery->groups);

        $havingCustom = [];
        $used = [];
        $useWhere = [];
        $has_having = False;

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
	                        	$query = $query->where(function($q) use($keys, $i, $data, $sign)
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
		if(!empty($directions))
        {
            $directions = array_map(function($item)
            {
                return $item>0?'asc':'desc';
            }, $directions);
            $orderRequest = [];
            foreach($keys as $index=>$key)
            {
                $orderRequest[$key] = $directions[$index];
            }
            foreach($orderRequest as $key=>$direction)
            {
            	$originalQuery->orderBy($key, $direction);
            }
        }
		return $request;
	}


}

use Table;
class ColumnsTester
{
	const PATTERN = '/([^ ]+)( +(as)? +([^. ]+))?/';
	const PATTERN_TABLE= '/([^ ]+)( +(as)? +([^. ]+))?/';
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
			preg_match(ColumnsTester::PATTERN_TABLE, $item , $data);
			//TODO:not sure => renamed table
			if(count($data)>2)
			{
				$item = $data[1];
				$previous[$data[4]] = Table::getColumnList($item);
			}
			$previous[$item] = Table::getColumnList($item);
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
