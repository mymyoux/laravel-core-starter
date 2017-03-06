<?php
namespace Core\Api;
use Request;
use Core\Exception\Exception as CoreException;
class ApiResponse
{
    public $value;
    public $exception;
    public $stats;
    public $apiData;
    public function isException()
    {
        return isset($this->exception);
    }
    public function getException()
    {
        return $this->exception;
    }
    public function isApiException()
    {
        return isset($this->exception) && isset($this->exception["api"]) && $this->exception["api"] === True;
    }
    public function getNewException()
    {
        if(!isset($this->exception))
        {
            return NULL;
        }
        $data = $this->exception;
        $cls = $data["type"];
        //TODO:careful with child classes
        if($cls != static::class && is_subclass_of($cls,CoreException::class))
        {
            $exception = $cls::unserialize($data);
        }else
        {
            $message = isset($data["message"])?$data["message"]:"";
            $code = isset($data["code"])?$data["code"]:NULL;
           /// $exception = new $cls($message, $code);
            $exception = new CoreException($message, $code);
           
        }
        $exception->file = $data["file"];
        $exception->line = $data["line"];
        $exception->trace = $data["trace"];
        return $exception;
    }
}
