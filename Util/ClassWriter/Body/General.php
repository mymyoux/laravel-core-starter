<?php

namespace Core\Util\ClassWriter\Body;

class General
{
    public static function getTable($name)
    {
        if(isset(static::$tables[$name]))
        {
            $cls = static::$tables[$name];
            return new $cls;
        }
        return NULL;
    }
	public static function hasTable($name)
    {
        return isset(static::$tables[$name]);
    }
    public static function getTableList()
    {
        return array_keys(static::$tables);
    }
    public static function hasColumn($tablename, $column)
    {
        return static::getTable($tablename)->hasColumn($column);
    }
    public static function getColumnType($tablename, $column)
    {
        return static::getTable($tablename)->hasColumn($column);
    }
    public static function getColumnList($tablename)
    {
        return static::getTable($tablename)->getColumnList();
    }
}
