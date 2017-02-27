<?php
/**
 * Created by PhpStorm.
 * User: jeremy.dubois
 * Date: 11/10/2014
 * Time: 19:14
 */

namespace Core\Exception;


class ApiException extends Exception{

    const ERROR_NOT_ALLOWED = "not_allowed";
	const ERROR_NOT_ALLOWED_FROM_FRONT = "not_allowed_from_front";
	public static $ERRORS = array(
        ApiException::ERROR_NOT_ALLOWED=>1,
		ApiException::ERROR_NOT_ALLOWED_FROM_FRONT=>2,
	);
    public $object;
    protected $cleanMessage;
    public $fatal = true;
    public function __construct($message = "", $code = 0, Exception $previous = null, $object = NULL, $fatal = true) {

        $this->fatal = $fatal;
    	$this->cleanMessage = $message;
        if($code == 0)
        {
        	if(isset(ApiException::$ERRORS[$message]))
        	{
        		$code = ApiException::$ERRORS[$message];

        	}
        }
        $message = '[API Exception] ' . $message;

        parent::__construct($message, $code, $previous);
        $this->object = $object;
    }
    public function toJsonObject()
    {
        $data = 
            [
                "message" => $this->getCleanErrorMessage(),
                "file" => $this->getFile(),
                "line" => $this->getLine(),
                "type" => get_class($this),
                "fatal" => $this->fatal,
                "code" => $this->getCode(),
                "api" => True,
                "trace" => $this->getTrace(),
            ];
        return $data;
    }
    public function getCleanErrorMessage()
    {
    	return $this->cleanMessage;
    }
    public function clone()
    {
        return new ApiException($this->cleanMessage, $this->code, $this->previous, $this->object, $this->fatal);
    }
     public static function unserialize($data)
    {
        $cls = $data["type"];
        $message = isset($data["message"])?$data["message"]:"";
        $code = isset($data["code"])?$data["code"]:NULL;
        $fatal = isset($data["fatal"])?$data["fatal"]:false;

        $exception = new $cls($message, $code, NULL, NULL, $fatal);
        return $exception;
    }

}
