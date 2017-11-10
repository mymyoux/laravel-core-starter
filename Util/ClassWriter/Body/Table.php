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

}
