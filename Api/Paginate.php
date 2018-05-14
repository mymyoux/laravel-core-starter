<?php
namespace Core\Api;
use Core\Exception\ApiException;
use Core\Exception\Exception;
use Illuminate\Http\Request;
use DB;
use Api;
use Illuminate\Database\Eloquent\Collection;
use ArrayObject;
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
	protected $mapping;
	protected $limit;
	public function __construct(Request $request)
	{
		$this->setRequest($request);
	}
	public function setRequest($request)
	{
		$this->request = $request;
	}
	protected $_initialized;
	protected function _initialize()
	{
		if(!$this->_initialized)
		{
			$paginate = $this->request->input("paginate");
			$this->keys = 	$paginate["keys"];
			
				if(isset($paginate["next"]))
					$this->next = $paginate["next"];
				if(isset($paginate["previous"]))
					$this->previous = $paginate["previous"];
				$this->directions = $paginate["directions"];
				$this->limit = $paginate["limit"];
				$this->_initialized = true;
		}
	}
	public function getLimit()
	{
		$this->_initialize();
		return $this->limit;
	}
	public function getNext()
	{
		$this->_initialize();
		return $this->next;
	}
	public function onResults($query, $data)
	{
		$query 	= method_exists($query, "getQuery")?$query->getQuery():$query;
		$is_collection = is_array($data) || ($data instanceof Traversable)?False:True;//$data instanceof ArrayObject || ;
		//TODO:handle others form of mapping
		$mapping = $this->mapping;
		if(isset($mapping) && is_string($mapping))
		{
			if($query->from != $mapping)
			{
				return;
			}
		}
		$apidata = [];
		$apidata["count"] = count($data);
		$keys = $this->keys;

		if(isset($this->next))
		{
			if ($is_collection)
			{
				$data = $data->filter(function($item) use($keys)
				{
					foreach($keys as $index=>$key)
					{
						if (!isset($item->$key)) continue;
						
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
				});
			}
			else
			{
				
				$data = array_values(array_filter($data, function($item) use($keys)
				{
					foreach($keys as $index=>$key)
					{
						if (!isset($item->$key)) continue;
						
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
		}
		 if(isset($this->previous))
        {
			if($is_collection)
			{
				$data = $data->filter(function($item) use($keys)
				{
					foreach($keys as $index=>$key)
					{
						if (!isset($item->$key)) continue;
						
						$direction = $this->directions[$index];
						if($direction>0)
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
				});
			}else
			{
				
				$data = array_values(array_filter($data, function($item) use($keys)
				{
					foreach($keys as $index=>$key)
					{
						if (!isset($item->$key)) continue;
						
						$direction = $this->directions[$index];
						if($direction>0)
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
		}
        if(!empty($data))
        {
            $previous = [];
            foreach($this->keys as $key)
            {
                if(isset($data[0]->{$key}))
                {
                    $previous[] = $this->formatPaginate( $data[0]->{$key} );
                }
            }
            $next = [];
            $len = sizeof($data)-1;
        
            foreach($this->keys as $key)
            {
                if(isset($data[$len]->{$key}))
                {
					$next[] = $this->formatPaginate( $data[$len]->{$key} );
                }
            }
            $apidata["next"] = $next;
            $apidata["previous"] = $previous;

		}
		$apidata["keys"] = $this->keys;
		$apidata["directions"] = $this->directions;
		$apidata["limit"] = $this->limit;
		$query->apidata = $apidata;
		Api::addApiData(["paginate"=>$apidata]);
	}

	private function formatPaginate( $value )
	{
		if ($value instanceof \Carbon\Carbon)
		{
			$reflection = new \ReflectionObject($value);
			$property 	= $reflection->getProperty('date');
			$original 	= $property->getValue($value);
			$value 		= $original; // only what to get the original date
		}

		return $value;
	}

	public function apply(&$request, $mapping = NULL, $havingOnly = NULL)
	{
		$originalQuery = method_exists($request, "getQuery")?$request->getQuery():$request;
		
		// if(!isset($originalQuery->processor) || !method_exists($originalQuery->processor, "setSelectListener"))
		// {
			// 	$originalQuery->processor = new \Core\Api\Paginate\Processor();
			
			// }
			//$originalQuery->processor->setSelectListener($this);
			
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

		$this->limit = $limit;


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
		$this->mapping = $mapping;
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


        if(isset($keys) || isset($limit))
        {
        	$originalQuery->limit($limit);
        }

        $originalQuery->where(function($query) use($next, $keys, $useWhere, $directions, $limit, $previous, $columns){


         	if(isset($keys) || isset($limit))
       	 	{
	            if(!empty($next) || !empty($previous))
	            {
	            	$data = !empty($next)?$next:$previous;
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
	        if(isset($keys) || isset($limit))
	        {
				if(isset($next) || isset($previous))
	            {
					$query->havingRaw("(", [], "and");
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
					$query->havingRaw(")", [], "");
	            }
	        }
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
                $orderRequest[$key] = isset($directions[$index]) ? $directions[$index] : $directions[0];
            }
            foreach($orderRequest as $key=>$direction)
            {
            	$originalQuery->orderBy($key, $direction);
            }
        }
		
		$request = new RequestWrapper($request, $this);
		return $request;
	}


}

use Tables\Table;
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
			//var_dump($select);
			if(($index = mb_strpos($select, "("))!==False)
			{
				$first = $index++;
				$opened = 1;
				$str_len = mb_strlen($select);
				while($opened && $index<$str_len)
				{
					$char = mb_substr($select, $index, 1);
					if($char == "(")
					{
						$opened++;
					}else
					if($char == ")")
					{
						$opened--;
					}
					$index++;
				}
				//TODO: maybe rename first part as unmatching column
				$select = mb_substr($select, 0, $first).mb_substr($select, $index);
			}
			$currentHavingOnly = [];
        	$havingColumns = array_merge($havingColumns, array_reduce(explode(",", $select), function($previous, $item) use($select, &$currentHavingOnly)
    		{
    			$item = str_replace('‘', '', $item);
    			$item = trim($item);
				$data = [];
    			preg_match(ColumnsTester::PATTERN, $item , $data);
    			$having = [$data[1]];
    			if(count($data)>2)
    			{
					$having[] = $data[4];
					$currentHavingOnly[] = $data[4];
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
				//var_dump($column);
				$this->havingColumns[] = $column;
			}
		}
		
		$this->havingOnly = isset($havingOnly)?$havingOnly:[];
		$this->havingOnly = array_reduce($this->havingOnly, function($previous, $item)
		{
			$columns = $this->getHavingColumns($item);
			$previous = array_merge($previous, $columns);
			return $previous;
		}, []);

		//add as
		$this->havingOnly = array_merge($currentHavingOnly, $this->havingOnly);
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
		return $this->needsHaving || !empty($this->havingOnly); 
	}
	protected function getHavingColumns($name)
	{
		$cols = NULL;
		if(mb_strpos($name, " as ")!==False)
		{
			$parts = explode(" as ", $name);
			$first = implode(" as ",array_slice($parts, 0, count($parts)-1));
			$second = $parts[count($parts)-1];
			
			$cols = $this->getHavingColumns($first);
			if(!empty($cols))
			{
				return array_merge([$second], $cols);
			}
			return [$second];
		}
		//agreggate
		if(strpos($name, "(")!==False)
		{
			// @ascheron: Bug IF without having => SQL: select `match`.*, IF(match.game_time > NOW(), 1, 0) AS upcoming from `match` inner join `game` on `game`.`id_game` = `match`.`id_game` inner join `game_mode` on `game_mode`.`id_game_mode` = `match`.`id_game_mode` where (`game_mode`.`is_active` = 1 and `game`.`is_active` = 1) group by `match`.`id_match` having (  ) order by `match`.`game_time` desc limit 10
			// if (strpos($name, "IF") !== false || strpos($name, "SUM") !== false || strpos($name, "MAX") !== false || strpos($name, "COUNT") !== false)
			// 	return null;

			$this->needsHaving = True;
			return $cols;
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
use Core\Util\Wrapper;
class RequestWrapper extends Wrapper
{
	protected $paginate;
	public function __construct($wrapped, $paginate)
	{
		parent::__construct($wrapped);
		$this->paginate = $paginate;
	}
	public function get()
	{
		$results = $this->wrapped->get();
		$this->paginate->onResults($this->wrapped, $results);
		return $results;
	}
	public function __call($name, $params)
	{
		$result = $this->wrapped->$name(...$params);
		if($result === $this->wrapped)
			return $this;
		return $result;
	}
}
