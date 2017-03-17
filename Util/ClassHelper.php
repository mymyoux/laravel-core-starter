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
		            for ($j=$i+1;$j<count($tokens);$j++) {
		                if ($tokens[$j] === '{') {
		                    $class = $tokens[$i+2][1];
		                    break 2;
		                }
		            }
		        }else
		        if ($tokens[$i][0] === \T_NAMESPACE) {
		            for ($j=$i+1;$j<count($tokens);$j++) {
		                if ($tokens[$j] === ';') {
		                    $namespace = join("", array_map(function($item){return $item[1];},array_slice($tokens, $i+2,$j-$i-2)));//$tokens[$i+2][1];
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
		return $this->getInformations($path)->namespace;
	}
	public static function getFullClassName($path)
	{
		return $this->getInformations($path)->class;
	}
	public static function getF2ullClassName($path)
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
		            for ($j=$i+1;$j<count($tokens);$j++) {
		                if ($tokens[$j] === '{') {
		                    $class = $tokens[$i+2][1];
		                    break 2;
		                }
		            }
		        }else
		        if ($tokens[$i][0] === \T_NAMESPACE) {
		            for ($j=$i+1;$j<count($tokens);$j++) {
		                if ($tokens[$j] === ';') {
		                    $namespace = join("", array_map(function($item){return $item[1];},array_slice($tokens, $i+2,$j-$i-2)));//$tokens[$i+2][1];
		                    break;
		                }
		            }
		        }
		    }
		}
		return ($namespace??"")."\\".$class;
	}
}
