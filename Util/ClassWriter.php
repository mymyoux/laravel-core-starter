<?php 
namespace Core\Util;
use Core\Util\ClassWriter\Uses;
use Core\Util\ClassWriter\Property;
use Core\Util\ClassWriter\Constant;
use Core\Util\ClassWriter\Method;
use ReflectionMethod;
class ClassWriter
{
	protected $namespace;
	protected $uses;
	protected $classname;
	protected $extends;
	protected $implements;

	protected $constants;
	protected $properties;

	protected $methods;


	public function __construct()
	{
		$this->uses = [];
		$this->extends = [];
		$this->implements = [];
		$this->constants = [];
		$this->properties = [];
		$this->methods = [];
	}
	public function setNamespace($namespace)
	{
		$this->namespace = $namespace;
	}
	public function addUse($path, $alias = NULL)
	{
		$this->uses[] = new Uses($path, $alias);
	}
	public function setClassName($name)
	{
		$this->classname = $name;
	}
	public function addProperty($name, $visibility = "public", $static = False, $value = NULL)
	{
		$this->properties[] = new Property($name, $visibility, $static, $value);
	}
	public function addConstant($name, $value)
	{
		$this->constants[] = new Constant($name, $value);
	}
	public function addFunction($name, $params, $body, $visibility = "public", $static = False)
	{
		$this->methods[] = new Method($name, $params, $body, $visibility, $static);
	}
	public function addMethod($cls, $function, $name = NULL, $params = NULL, $visibility = NULL, $static = NULL)
	{
		$func = new ReflectionMethod($cls, $function);
		$filename = $func->getFileName();
		$start = $func->getStartLine();
		$end = $func->getEndLine();

		$length = $end - $start;

		$source = file($filename);
		$head = $source[$start-1];
		$matches = [];
		preg_match("/([a-z]+) +(static)? *function +([^\( ]+)\(([^)]*)\)/i", $head, $matches);
		if(!isset($visibility))
		{
			$visibility = $matches[1];
		}
		if(!isset($static))
		{
			$static = strlen($matches[2])>0;
		}
		if(!isset($name))
		{
			$name = $matches[3];
		}
		if(!isset($params))
		{
			$params = $matches[4];
		}
		$body = implode("", array_slice($source, $start, $length));
		return $this->addFunction($name, $params, $body, $visibility, $static);
	}
	protected function tab($length)
	{
		$tab = "";
		for($i=0; $i<$length; $i++)
		{
			$tab.="\t";
		}
		return $tab;
	}

	public function export()
	{
		$cls = "<?php\n";
		$tab = $this->tab(0);

		//namespace
		if(isset($this->namespace))
			$cls.= $tab."namespace ".$this->namespace.";\n";

		//use statements
		if(!empty($this->uses))
		{
			foreach($this->uses as $uses)
			{
				$cls.= $tab."use ".$uses->getPath().($uses->hasAlias()?" as ".$uses->getAlias():"").";\n";
			}
		}

		//class declaration
		if(isset($this->classname))
		{
			$cls.= $tab."class ".$this->classname;
			if(!empty($this->extends))
			{
				$cls.= " extends ".implode(", ", $this->extends);
			}
			if(!empty($this->implements))
			{
				$cls.= " implements ".implode(", ", $this->implements);
			}
			$cls.= "\n{\n";
			$tab = $this->tab(1);
		}

		if(!empty($this->constants))
		{
			foreach($this->constants as $constant)
			{
				$cls.= $tab."const ".$constant->getName().($constant->hasValue()?" = ".$constant->getEscapedValue():"").";\n";
			}
		}

		if(!empty($this->properties))
		{
			foreach($this->properties as $property)
			{
				$cls.= $tab.($property->hasVisibility()?$property->getVisibility()." ":"").($property->isStatic()?'static ':'')."$".$property->getName().($property->hasValue()?" = ".$property->getEscapedValue():"").";\n";
			}
		}
		if(!empty($this->methods))
		{
			foreach($this->methods as $method)
			{
				$cls.= $tab.($method->hasVisibility()?$method->getVisibility()." ":"").($method->isStatic()?'static ':'');

				$cls.= "function ".$method->getName()."(".($method->hasParams()?$method->getParams():"").")\n";
				$cls.= $method->getBody();
			}
		}


		$tab = $this->tab(0);
		if(isset($this->classname))
		{
			$cls.= $tab."}\n";
		}
		return $cls;
	}
	public function write($path)
	{
		$text = $this->export();
		file_put_contents($path, $text);
	}


}


