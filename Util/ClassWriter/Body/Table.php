<?php
namespace Core\Util\ClassWriter\Body;
class Table
{
	private function getColumnList()
    {
        return array_keys($this->casts);
    }
    private function getColumnType($name)
    {
        if(isset($this->casts[$name]))
        {
            return $this->casts[$name];
        }
        return NULL;
    }
    private function hasColumn($name)
    {
    	return isset($this->casts[$name]);
    }
    // public function __get($name)
    // {
    // 	return constant("static::$name");
    // }
    // public function __call($name, $arguments)
    // {
    //     return call_user_func_array([static::class, $name], $arguments);
    // }
    // public static function __callStatic($name, $arguments)
    // {
    //     if(method_exists(static::class, $name))
    //     {
    //        return call_user_func_array([static::class, $name], $arguments);
    //     }else
    //     {
    //         $table = Db::table(static::TABLE);
    //        return call_user_func_array([$table, $name], $arguments);
    //     }
    // }
}
