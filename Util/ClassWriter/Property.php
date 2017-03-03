<?php 
namespace Core\Util\ClassWriter;

class Property
{
	protected $name;
	protected $visibility;
	protected $_isStatic;
	protected $value;
	public function __construct($name, $visibility, $isStatic, $value)
	{
		$this->name = $name;
		$this->visibility = $visibility;
		$this->_isStatic = $isStatic;
		$this->value = $value;
	}
	public function getName()
	{
		return $this->name;
	}
	public function getVisibility()
	{
		return $this->visibility;
	}
	public function hasValue()
	{
		return isset($this->value);
	}
	public function getValue()
	{
		return $this->value;
	}
	public function hasVisibility()
	{
		return isset($this->visibility);
	}
	public function isStatic()
	{
		return $this->_isStatic;
	}
	public function getEscapedValue()
	{
		if(is_array($this->value))
		{
			return var_export($this->value, True);
		}
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


