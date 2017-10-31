<?php 
namespace Core\Util;
use Core\Util\ClassWriter\Uses;
use Core\Util\ClassWriter\Property;
use Core\Util\ClassWriter\Constant;
use Core\Util\ClassWriter\Method;
use ReflectionMethod;
class ClassHelper
{
	public static function getInformations($path)
	{
		$fp = fopen($path, 'r');
		$class = $buffer = '';
		$i = 0;
		$namespace = "";
		while (!$class) {
		    if (feof($fp)) break;

		    $buffer .= fread($fp, 512);
		    $tokens = @token_get_all($buffer);

		    if (strpos($buffer, '{') === false) continue;

		    for (;$i<count($tokens);$i++) {
		        if ($tokens[$i][0] === \T_CLASS) {
					if(!isset($tokens[$i-1]) || (!isset($tokens[$i-1][1]) || $tokens[$i-1][1]!="::"))
					{
						
						for ($j=$i+1;$j<count($tokens);$j++) {
							if ($tokens[$j] === '{') {
								if(isset($tokens[$i+2][1]))
								{
									$class = $tokens[$i+2][1];
									break 2;
								}else
								{
									dd($tokens[$i-1]);
								}
							}
						}
					}
		        }else
		        if ($tokens[$i][0] === \T_NAMESPACE) {
		            for ($j=$i+1;$j<count($tokens);$j++) {
						if ($tokens[$j] === '{') {
							break;
						}
		                if ($tokens[$j] === ';') {
		                    $namespace = join("", array_map(function($item){
								if(!isset($item[1]))
								{
									return "";
								}
								return $item[1];},array_slice($tokens, $i+2,$j-$i-2)));//$tokens[$i+2][1];
							break;
		                }
		            }
		        }
		    }
		}
		$std = new \stdClass();
		$std->namespace = $namespace;
		$std->class = $class;
		$std->fullname =  ($namespace??"")."\\".$class;
		return $std;
	}
	public static function getNamespace($path)
	{
		return static::getInformations($path)->namespace;
	}
	public static function getFullClassName($path)
	{
		return static::getInformations($path)->fullname;
	}
	public static function getMethodBody($path, $withHeaders = False)
	{
		list($cls, $function) = explode("@", $path);
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
		return ($withHeaders?$head:"").$body;
	}
}
