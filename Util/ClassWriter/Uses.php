<?php 
namespace Core\Util\ClassWriter;

class Uses
{
	protected $path;
	protected $alias;
	public function __construct($path, $alias)
	{
		$this->path = $path;
		$this->alias = $alias;
	}
	public function getPath()
	{
		return $this->path;
	}
	public function getAlias()
	{
		return $this->alias;
	}
	public function hasAlias()
	{
		return isset($this->alias);
	}


}


