<?php

namespace Core\Api\Annotations;

use Core\Exception\Exception;

class CoreAnnotation
{
    protected $classAnnotation;
    public function setIsFromClass($value)
    {
        $this->classAnnotation = $value;
    }
     public static function getMiddleware()
    {
        $fullname = static::class;
        $index = strrpos($fullname, 'Annotations');
        $name = substr($fullname, $index+strlen('Annotations')+1);
        
        return 'Core\Http\Middleware\Api\\'.$name;
    }
    public function toMiddleWareParams()
    {
        $serialized = ["cls"=>static::class, "data"=>$this->serialize()];
        return base64_encode(json_encode($serialized));
    }
    public function handleAnnotations($annotations)
    {

    }
    public function handle($config)
    {
        $middleware = $this->getMiddleware();
        if(!$middleware)
        {
            return $config;
        }
        $strMiddleware = $middleware;
        $params = $this->toMiddleWareParams();
        if(isset($params))
        {
            $strMiddleware.=":".$params;
        }
        $config->middlewares[] = $strMiddleware;
        return $config;
    }




    public function key()
    {
        return $this->_key;
    }

    public function boot()
    {

    }
    public function validate($object)
    {
        return $object;
    }
    public function serialize()
    {
        $reflection = new \ReflectionClass(static::class);
        $data = [];
        $properties = $reflection->getProperties();
        foreach($properties as $property)
        {
            if(isset($this->{$property->name}))
                $data[$property->name] = $this->{$property->name};
        }
        return $data;
    }
    public function unserialize($data)
    {
        foreach($data as $key=>$value)
        {
            $this->$key = $value;
        }
    }
    public function toArray()
    {
        $data = [];
        foreach($this as $key=>$value)
        {
            if(substr($key, 0, 1)=="_")
                continue;
            $data[$key] = $value;
        }
        $data["cls"] = static::class;
        return $data;
    }
}

