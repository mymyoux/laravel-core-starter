<?php
namespace Core\Api;
use Request;
class ApiResponse
{
    public $value;
    public $exception;
    public $stats;
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
        if($cls != static::class && method_exists($cls, "unserialize"))
        {
            $exception = $cls::unserialize($data);
        }else
        {
            $message = isset($data["message"])?$data["message"]:"";
            $code = isset($data["code"])?$data["code"]:NULL;
            $exception = new $cls($message, $code);
        }
        $exception->file = $data["file"];
        $exception->line = $data["line"];
        $exception->trace = $data["trace"];
        return $exception;
    }
}
