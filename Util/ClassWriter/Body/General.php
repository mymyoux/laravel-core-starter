<?php

namespace Core\Util\ClassWriter\Body;

class General
{
	public static function hasTable($name)
    {
        return in_array($name, static::$_tables);
    }
    public static function getTableList()
    {
        return static::$_tables;
    }
}
