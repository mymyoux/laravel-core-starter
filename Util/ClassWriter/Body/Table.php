<?php

namespace Core\Util\ClassWriter\Body;

class Table
{
	public static function getColumnList()
    {
        return array_keys(static::$_columns);
    }
    public static function getColumnType($name)
    {
        if(isset(static::$_columns[$name]))
        {
            return static::$_columns[$name];
        }
        return NULL;
    }
    public static function hasColumn($name)
    {
    	return isset(static::$_columns[$name]);
    }
    public function __get($name)
    {
    	return constant("static::$name");
    }
    public function __call($name, $arguments)
    {
    	return call_user_func_array([static::class, $name], $arguments);
    }
}
