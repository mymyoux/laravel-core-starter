<?php 
namespace Core\Util\ClassWriter;

class Constant
{
	protected $name;
	protected $value;
	public function __construct($name, $value)
	{
		$this->name = $name;
		$this->value = $value;
	}
	public function getName()
	{
		return $this->name;
	}
	
	public function hasValue()
	{
		return isset($this->value);
	}
	public function getValue()
	{
		return $this->value;
	}
	public function getEscapedValue()
	{
		if(!is_string($this->value))
		{
			return $this->value;
		}
		if($this->value == "NULL")
		{
			return $this->value;
		}
		if(strtolower($this->value) == "true" || strtolower($this->value) == "false")
		{
			return $this->value;
		}
		if(is_numeric($this->value))
		{
			return $this->value;
		}
		if(mb_substr(0, 1) != "'" && mb_substr(0, 1)!='"')
		{
			return "'".str_replace('\'', '\\\'', $this->value)."'";
		}
	}
}


