<?php
namespace Core\Api;
use Core\Exception\ApiException;
use Core\Exception\Exception;
use Illuminate\Http\Request;
use DB;
class Paginate
{
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
	public function apply($request, $mapping = NULL, $having = NULL)
	{
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


		$has_group = !empty($request->getQuery()->groups);

        $orderCustom = [];
        $used = [];
        $useWhere = [];
        $force_having = False;
        foreach($keys as $index=>$k)
        {
        	$force_having = False;
            $used[$index] = True;
            if($used[$index] && isset($having))
            {
                if(in_array($k, $having))
                {
                   $force_having = True;
                }
            }
            if(isset($mapping) && $used[$index])
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
            $useWhere[$index] = $force_having?"having":"where";
        }
        $has_been_partially_filtered = False;

        //TODO:convert to query builder
        //TODO:utiliser la précédence de AND sur OR
        //TODO: utiliser having sur les propriétés qui nécessite, where sur les autres (pa rapport aux keys)
        //TODO:detect groups => having for all
        

        //force to nest previous wheres
        $query = $request->getQuery();
        if(!empty($query->wheres))
        {
	        $querynested = $query->newQuery();
	      	$querynested->wheres = $query->wheres;
	      	$query->wheres = [["type"=>"nested", "query"=>$querynested,"boolean"=>"and"]];
        }
        if(!empty($query->havings))
        {
	        $querynested = $query->newQuery();
	      	$querynested->havings = $query->havings;
	      	$query->havings = [["type"=>"nested", "query"=>$querynested,"boolean"=>"and"]];
        }


        if(isset($keys) || isset($limit))
        {
        	$request->limit($limit);
        }

        $request->where(function($query) use($next, $keys, $useWhere, $directions, $limit, $previous, $used, $force_having){


         if(isset($keys) || isset($limit))
        {
            if(isset($next))
            {
                $first = True;
                foreach($keys as $index=>$key)
                {
                	if($useWhere[$index] == "having" && strpos($key, ".")===False)
                	{
                		//not where
                		continue;
                	}
                    $direction = $directions[$index];
                    if($direction>0)
                    {
                        if(mb_strlen($next[$index]))
                        {
                        	$query->where($key, ">", $next[$index],  $first?'and':'or');
                        }else
                        {
                        	if($first)
                        	{
	                        	$query->where(function($q) use($key)
	                        	{
	                        		$q->orWhereNull($key)
	                        		->orWhere($key, ">", $next[$index]);
	                        	});
                        	}else
                        	{
	                        	$query->orWhere(function($q) use($key)
	                        	{
	                        		$q->orWhereNull($key)
	                        		->orWhere($key, ">", $next[$index]);
	                        	});

                        	}
                        }
                    }else
                    {
                    	 if(mb_strlen($next[$index]))
                        {
                        		$query = $query->where($key, "<", $next[$index],  $first?'and':'or');
                        }else
                        {
                        	if($first)
                        	{
	                        	$query = $query->where(function($q) use($key)
	                        	{
	                        		$q->orWhereNull($key)
	                        		->orWhere($key, "<", $next[$index]);
	                        	});
                        	}else
                        	{
	                        	$query = $query->orWhere(function($q) use($key)
	                        	{
	                        		$q->orWhereNull($key)
	                        		->orWhere($key, "<", $next[$index]);
	                        	});

                        	}
                        }
                    }

                    for($i=0;$i<$index; $i++)
                    {
                    	if($useWhere[$i] == "having" && strpos($keys[$i], ".")===False)
	                	{
	                		//not where
	                		continue;
	                	}
                        if(mb_strlen($next[$i]))
                        {
                    		$query = $query->where($keys[$i], "=", $next[$i],'and');
                        }else
                        {
                        	$query = $query->where(function($q) use($keys, $i, $next, $directions)
                        	{
                        		$q->whereNull($keys[$i], $next[$i])
                        		->orWhere($keys[$i], $directions[$i]<0?"<":">", $next[$i]);
                        	});
                        }
                    }
                    $first = False;
                }
            }
            if(isset($previous))
            {
            	$first = True;
                foreach($keys as $index=>$key)
                {
                	if($useWhere[$index] == "having" && strpos($key, ".")===False)
                	{
                		//not where
                		continue;
                	}
                    $direction = $directions[$index];
                    if($direction<0)
                    {
                        if(mb_strlen($next[$index]))
                        {
                        	$query->where($key, ">", $next[$index],  $first?'and':'or');
                        }else
                        {
                        	if($first)
                        	{
	                        	$query->where(function($q) use($key)
	                        	{
	                        		$q->orWhereNull($key)
	                        		->orWhere($key, ">", $next[$index]);
	                        	});
                        	}else
                        	{
	                        	$query->orWhere(function($q) use($key)
	                        	{
	                        		$q->orWhereNull($key)
	                        		->orWhere($key, ">", $next[$index]);
	                        	});

                        	}
                        }
                    }else
                    {
                    	 if(mb_strlen($next[$index]))
                        {
                        		$query = $query->where($key, "<", $next[$index],  $first?'and':'or');
                        }else
                        {
                        	if($first)
                        	{
	                        	$query = $query->where(function($q) use($key)
	                        	{
	                        		$q->orWhereNull($key)
	                        		->orWhere($key, "<", $next[$index]);
	                        	});
                        	}else
                        	{
	                        	$query = $query->orWhere(function($q) use($key)
	                        	{
	                        		$q->orWhereNull($key)
	                        		->orWhere($key, "<", $next[$index]);
	                        	});

                        	}
                        }
                    }

                    for($i=0;$i<$index; $i++)
                    {
                    	if($useWhere[$i] == "having" && strpos($keys[$i], ".")===False)
	                	{
	                		//not where
	                		continue;
	                	}
                        if(mb_strlen($next[$i]))
                        {
                    		$query = $query->where($keys[$i], "=", $next[$i],'and');
                        }else
                        {
                        	$query = $query->where(function($q) use($keys, $i, $next, $directions)
                        	{
                        		$q->whereNull($keys[$i], $next[$i])
                        		->orWhere($keys[$i], $directions[$i]>0?"<":">", $next[$i]);
                        	});
                        }
                    }
                    $first = False;
                }
            }
        }
        });
    dd($request->toSql());
		//TODO:add Use having to not use this callback
		 $request->having(function($query) use($next, $keys, $useWhere, $directions, $limit, $previous, $used, $force_having){
		         if(isset($keys) || isset($limit))
		        {
		            if(isset($next))
		            {
		                $first = True;
		                foreach($keys as $index=>$key)
		                {
		                	if($useWhere[$index] != "having")
		                	{
		                		//not where
		                		continue;
		                	}
		                    $direction = $directions[$index];
		                    if($direction>0)
		                    {
		                        if(mb_strlen($next[$index]))
		                        {
		                        	$query->having($key, ">", $next[$index],  $first?'and':'or');
		                        }else
		                        {
		                        	if($first)
		                        	{
			                        	$query->having(function($q) use($key)
			                        	{
			                        		$q->orHavingNull($key)
			                        		->orHaving($key, ">", $next[$index]);
			                        	});
		                        	}else
		                        	{
			                        	$query->orHaving(function($q) use($key)
			                        	{
			                        		$q->orHavingNull($key)
			                        		->orHaving($key, ">", $next[$index]);
			                        	});

		                        	}
		                        }
		                    }else
		                    {
		                    	 if(mb_strlen($next[$index]))
		                        {
		                        		$query = $query->having($key, "<", $next[$index],  $first?'and':'or');
		                        }else
		                        {
		                        	if($first)
		                        	{
			                        	$query = $query->having(function($q) use($key)
			                        	{
			                        		$q->orHavingNull($key)
			                        		->orHaving($key, "<", $next[$index]);
			                        	});
		                        	}else
		                        	{
			                        	$query = $query->orHaving(function($q) use($key)
			                        	{
			                        		$q->orHavingNull($key)
			                        		->orHaving($key, "<", $next[$index]);
			                        	});

		                        	}
		                        }
		                    }

		                    for($i=0;$i<$index; $i++)
		                    {
		                    	if($useHaving[$i] != "having" )
			                	{
			                		//not having
			                		continue;
			                	}
		                        if(mb_strlen($next[$i]))
		                        {
		                    		$query = $query->having($keys[$i], "=", $next[$i],'and');
		                        }else
		                        {
		                        	$query = $query->having(function($q) use($keys, $i, $next, $directions)
		                        	{
		                        		$q->havingNull($keys[$i], $next[$i])
		                        		->orHaving($keys[$i], $directions[$i]<0?"<":">", $next[$i]);
		                        	});
		                        }
		                    }
		                    $first = False;
		                }
		            }
		            if(isset($previous))
		            {
		            	$first = True;
		                foreach($keys as $index=>$key)
		                {
		                	if($useHaving[$index] == "having" && strpos($key, ".")===False)
		                	{
		                		//not having
		                		continue;
		                	}
		                    $direction = $directions[$index];
		                    if($direction<0)
		                    {
		                        if(mb_strlen($next[$index]))
		                        {
		                        	$query->having($key, ">", $next[$index],  $first?'and':'or');
		                        }else
		                        {
		                        	if($first)
		                        	{
			                        	$query->having(function($q) use($key)
			                        	{
			                        		$q->orHavingNull($key)
			                        		->orHaving($key, ">", $next[$index]);
			                        	});
		                        	}else
		                        	{
			                        	$query->orHaving(function($q) use($key)
			                        	{
			                        		$q->orHavingNull($key)
			                        		->orHaving($key, ">", $next[$index]);
			                        	});

		                        	}
		                        }
		                    }else
		                    {
		                    	 if(mb_strlen($next[$index]))
		                        {
		                        		$query = $query->having($key, "<", $next[$index],  $first?'and':'or');
		                        }else
		                        {
		                        	if($first)
		                        	{
			                        	$query = $query->having(function($q) use($key)
			                        	{
			                        		$q->orHavingNull($key)
			                        		->orHaving($key, "<", $next[$index]);
			                        	});
		                        	}else
		                        	{
			                        	$query = $query->orHaving(function($q) use($key)
			                        	{
			                        		$q->orHavingNull($key)
			                        		->orHaving($key, "<", $next[$index]);
			                        	});

		                        	}
		                        }
		                    }

		                    for($i=0;$i<$index; $i++)
		                    {
		                    	if($useHaving[$i] == "having" && strpos($keys[$i], ".")===False)
			                	{
			                		//not having
			                		continue;
			                	}
		                        if(mb_strlen($next[$i]))
		                        {
		                    		$query = $query->having($keys[$i], "=", $next[$i],'and');
		                        }else
		                        {
		                        	$query = $query->having(function($q) use($keys, $i, $next, $directions)
		                        	{
		                        		$q->havingNull($keys[$i], $next[$i])
		                        		->orHaving($keys[$i], $directions[$i]>0?"<":">", $next[$i]);
		                        	});
		                        }
		                    }
		                    $first = False;
		                }
		            }
		        }
        });
        dd($request->toSql());
        exit();
        if($this->hasOrder())
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
            $orderRequest = [];
            foreach($keys as $index=>$key)
            {
                if(!$used[$index])
                {
                    continue;
                }

                if(isset($orderCustom[$index]))
                {
                    $orderRequest[] = new expression($orderCustom[$index]." ".$direction[$index]);
                }else
                {
                    $orderRequest[$key] = $direction[$index];
                }
            }
            /*
            $new_order = NULL;
            if(isset($orderMapping) && !empty($orderMapping))
            {
                foreach($orderMapping as $orderM)
                {
                    if($orderM->match($this->key))
                    {
                        $new_order = $orderM;
                        break;
                    }
                }
                if(isset($new_order))
                {
                    $orderRequest[$new_order->getColumn()]=$new_order->getOrder();
                    $request->addColumns([$this->key=>new Expression("")]);
                }
            }*/
            /*
            if(isset($orderMapping))
            {
                if(is_array($orderMapping))
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
            if(!empty($orderRequest))
                $request = $request->order($orderRequest);
        }
		return $request;
	}
}
