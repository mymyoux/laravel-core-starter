<?php
namespace Core\Api\Annotations;
use Core\Exception\Exception;
use Core\Exception\ApiException;


/**
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class Paginate extends CoreAnnotation
{
	public $allowed;
	public $keys;
	public $directions;
	public $limit;
    public function boot()
    {
    	if(isset($this->keys))
    	{
    		$this->keys = array_map("trim", explode(",", $this->keys));
    	}
    	if(isset($this->allowed))
    	{
    		$this->allowed = array_map("trim", explode(",", $this->allowed));
    	}
    	if(isset($this->limit))
    	{
    		$this->limit = (int) $this->limit;
    	}
    	if(isset($this->directions))
    	{
    		$this->directions = array_map(function($item){return (int)trim($item);}, explode(",", $this->directions.""));
    	}
    }
    public function format($paginate)
    {
    	if(!isset($paginate))
    	{
    		$paginate = [];
    	}
    	if(!isset($paginate["keys"]))
    	{
    		$paginate["keys"] = $this->keys;
    	}
    	if(!isset($paginate["limit"]))
    	{
    		$paginate["limit"] = $this->limit;
    	}
    	if(isset($paginate["limit"]))
    	{
    		$paginate["limit"] = (int) $paginate["limit"];
    	}
    	if(!isset($paginate["directions"]))
    	{
    		$paginate["directions"] = $this->directions;
    	}
    	if(isset($paginate["directions"]))
    	{
    		$paginate["directions"] = array_map(function($item){return (int)trim($item);}, $paginate["directions"]);
    	}
    	return $paginate;
    }
    public function isAllowed($key)
    {
    	return in_array($key, $this->allowed);
    }
}
