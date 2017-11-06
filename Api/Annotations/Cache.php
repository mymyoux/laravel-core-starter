<?php
namespace Core\Api\Annotations;
use Core\Exception\Exception;
use Core\Exception\ApiException;
/**
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class Cache extends CoreAnnotation
{
	public $ids;
	public $name;
	public $keys;
    public $invalid;
    
    public function boot()
    {
    	if (isset($this->keys))
    	{
    		$this->keys = array_map("trim", explode(",", $this->keys));
        }

        if (isset($this->ids))
    	{
    		$this->ids = array_map("trim", explode(",", $this->ids));
        }

        if (!isset($this->name))
        {
            $this->name = 'coucou';
        }

        if(!isset($this->invalid))
    	{
            $this->invalid = false;
        }

        if (!$this->keys)
            $this->keys = [];
        
        if (!$this->ids)
            $this->ids = [];
    }
    public function format($object)
    {
    	return $object;
    }
}
