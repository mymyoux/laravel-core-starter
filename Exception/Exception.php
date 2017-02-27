<?php
/**
 * Created by PhpStorm.
 * User: jeremy.dubois
 * Date: 11/10/2014
 * Time: 19:14
 */

namespace Core\Exception;


class Exception extends \Exception
{
	public $file;
	public $line;
	public $trace;
    public $object;
    public function __construct($message = "", $code = 0, Exception $previous = null, $object = NULL) {
        parent::__construct($message, $code, $previous);
        $this->object = $object;
    }
    public function toJsonObject()
    {
    	$data = 
    		[
    			"message" => $this->getMessage(),
    			"file" => $this->getFile(),
    			"line" => $this->getLine(),
    			"type" => get_class($this),
    			"fatal" => False,
    			"code" => $this->getCode(),
    			"trace" => $this->getTrace(),
    		];
    	return $data;
    }
    protected static $fatals = 
    [
    	\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class
    ];
    public static function convertToJsonObject($exception)
    {
    	if(method_exists($exception, "toJsonObject"))
    	{
    		return $exception->toJsonObject();
    	}else
    	{
    		$cls = get_class($exception);
    		$data = 
    		[
    			"message" => $exception->getMessage(),
    			"file" => $exception->getFile(),
    			"line" => $exception->getLine(),
    			"type" => $cls,
    			"fatal" => in_array($cls, static::$fatals),
    			"code" => $exception->getCode(),
    			"trace" => $exception->getTrace(),
    		];
    		return $data;
    	}
    }
   
}
