<?php
namespace Core\Database;

trait TableTrait
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
    public function __call($name, $params)
    {
        if(in_array($name, ["getColumnList","getColumnType","hasColumn"]))
        {
            return $this->$name(...$params);
        }
        return parent::__call($name, $params);
    }
}