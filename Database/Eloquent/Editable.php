<?php
namespace Core\Database\Eloquent;
use ReflectionMethod;
use ReflectionClass;
use BadMethodCallException;
use Closure;
use Illuminate\Support\Str;
trait Editable
{
    private $methods = [];
    private $_mixins = [];
    private static $static_methods = [];
    private $builders_methods = [];
    private $_prepare_cache = [];
    public function __call($name, $params)
    {
        if(isset($this->methods[$name]))
        {
            if($this->methods[$name] instanceof Closure)
            {
                return call_user_func_array($this->methods[$name]->bindTo($this, static::class), $params);
            }
            return call_user_func_array($this->methods[$name], $params);
        }
        if(isset(static::$static_methods[$name]))
        {
            return static::$static_methods[$name](...$params);
        }
        //return parent::$name(...$params);
        try
        {

            return parent::__call($name, $params);
        }catch(\Exception $e)
        {
            $e;
        }
    }
    public function addEditable($name, $callback)
    {
        if($name == "buildArray")
        {
            $this->builders_methods[] = $callback;
        }else
        if($name == "prepareCache")
        {
            $this->_prepare_cache[] = $callback;
        }else
        $this->methods[$name] = $callback;
    }
    public function mixin($object)
    {
        
        $this->_mixins[] = is_string($object)?$object:get_class($object);
        $methods = (new ReflectionClass($object))->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );

        foreach ($methods as $method) { 
            $method->setAccessible(true);
            try
            {
                if(in_array($method->name,['buildArray','prepareCache']) || !method_exists($method->name, $this))
                {
                    if($method->getNumberOfParameters())
                    {  
                        continue;
                    }
                    $closure = $method->invoke($object);
                    if($closure instanceof Closure)
                    {
                        $this->addEditable($method->name, $closure);
                    }
                }

            }catch(\Exception $e)
            {
                dd($e);
            }
        }
        if(method_exists($object, 'handleEditable'))
        {
            $object->handleEditable($this);
        }
    }
    public static function __callStatic($name, $params)
    {
        if($name == "addEditable")
        {
            static::$static_methods[$name] = $params[0];
            return;
        }else
        {
            if(isset(static::$static_methods[$name]))
            {
                if (static::$static_methods[$name] instanceof Closure) {
                    return call_user_func_array(Closure::bind(static::$static_methods[$name], null, static::class), $params);
                }
        
                return call_user_func_array($static_methods[$name], $params);
            }
        }
        return parent::__callStatic($name, $params);
    }
    public function __sleep()
    {
        //TODO:what to do with parent sleep ? 
        // if(is_callable('parent::__sleep'))
        // {
        //     try
        //     {
        //         $keys = parent::__sleep();
        //     }catch(BadMethodCallException $e)
        //     {
        //         $keys = [];
        //     }
        // }
        $data = (array)$this;
        $keys= array_keys($data);
        return array_values(array_filter($keys, function($item)
        {
            return ends_with($item, "\x00methods")===False && ends_with($item, "\x00_prepare_cache")===False && ends_with($item, "\x00builders_methods")===False;
        }));
    } 
    public function __wakeup()
    {
        foreach($this->_mixins as $mixin)
        {
            $obj = new $mixin;
            $this->mixin($obj);
        }
    }
    public function getRelationValue($key)
    {
        
        // If the key already exists in the relationships array, it just means the
        // relationship has already been loaded, so we'll just return it out of
        // here because there is no need to query within the relations twice.
        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }
        // If the "attribute" exists as a method on the model, we will just assume
        // it is a relationship and will load and return results from the query
        // and hydrate the relationship's value on the "relationships" array.
       
        if (method_exists($this, $key)) {
            return $this->getRelationshipFromMethod($key);
        }
        if(isset($this->methods[$key]))
        {
            return $this->getRelationshipFromMethod($key);
        }
    }
    public function hasGetMutator($key)
    {
        $name = 'get'.Str::studly($key).'Attribute';
        return method_exists($this, $name) || isset($this->methods[$name]);
    }
    public function buildArray($data)
    {
        foreach($this->builders_methods as $builder)
        {
            $data = call_user_func_array($builder->bindTo($this, static::class), [$data]);
        }
        return $data;
    }
    public function prepareCache()
    {
        foreach($this->_prepare_cache as $builder)
        {
            call_user_func_array($builder->bindTo($this, static::class), []);
        }
    }
}