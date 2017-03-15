<?php 
namespace Core\Util;
use Core\Util\ClassWriter\Uses;
use Core\Util\ClassWriter\Property;
use Core\Util\ClassWriter\Constant;
use Core\Util\ClassWriter\Method;
use ReflectionMethod;
class ClassHelper
{
	public static function getFullClassName($path)
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
