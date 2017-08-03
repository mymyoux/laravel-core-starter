<?php 
namespace Core\Util;

class Wrapper
{
	protected $wrapped;
	public function __construct($wrapped)
	{
		$this->wrapped = $wrapped;
	}
	public function __call($name, $params)
	{
		return $this->wrapped->$name(...$params);
	}
	public function __get($name)
	{
		return $this->wrapped->$name;
	}
	public function __set($name, $value)
	{
		return $this->wrapped->$name = $value;
	}
	public function __isset($name)
	{
		return isset($this->wrapped->$name);
	}
	public function __unset($name)
	{
		unset($this->wrapped->$name);
	}
	public function __clone()
	{
		return clone $this->wrapped;
	}
	public function __toString()
	{
		return $this->wrapped;
	}
	public function __invoke($params)
	{
		return $this->wrapped(...$params);
	}
	public function __sleep()
	{
		return serialize($this->wrapped);
	}
	public function __wakeup()
	{
		return unserialize($this->wrapped);
	}
	public static function __callStatic($name, $params)
	{
		return $this->wrapped::$name(...$params);
	}
    public function __debugInfo() 
    {
        return $this->wrapped;
    }
}
