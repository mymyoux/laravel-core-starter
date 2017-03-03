<?php

namespace Core\Util\ClassWriter\Body;

class General
{
    public static function getTable($name)
    {
        $name = "Tables\\".strtoupper($name);
        return new $name();
    }
	public static function hasTable($name)
    {
        return in_array($name, static::$_tables);
    }
    public static function getTableList()
    {
        return static::$_tables;
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
