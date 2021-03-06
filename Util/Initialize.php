<?php
namespace
{
if (!function_exists('class_use_trait')) 
{
    function class_use_trait($class, $trait)
	{
		return in_array($trait, class_uses($class));
	}
}
if (!function_exists('first')) 
{
    /**
     * Return first element of array
     * @param array $array
     * @return any
     */
    function first($array)
    {
        if(!count($array))
            return NULL;
        return array_values($array)[0];
    }
}
if (!function_exists('is_url')) 
{
    /**
     * checks if it is an URL or not
     * @param string $text
     * @return bool
     */
    function is_url( $text )
    {
        return filter_var( $text, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED) !== false;
    }
}
if (!function_exists('replace4byte')) 
{
    function replace4byte($string)
    {
        return preg_replace_callback('%(?:
            \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
        )%xs', function( $r ){
            return '{emoji:' . bin2hex($r[0]) . '}';
        }, $string);
    }
}
if (!function_exists('emojiFront')) 
{
    function emojiFront($string)
    {
        return preg_replace_callback('/\{emoji:([0-9a-f]+)\}/', function( $r ){
            return pack('H*', $r[1]);
        }, $string);
    }
}
if(!class_exists('DD'))
{
    class DD extends \Exception
    {

    }
}
if (!function_exists('dd')) 
{
    /**
     * Display data as var_dump and kill the application
     * @param $data
     */
    function dd(...$data)
    {
        $stack = debug_backtrace();
        $line = $stack[0];
        echo $line["file"].":".$line["line"]."\n";
        //if call at root
        if(count($stack)>1)
        {
            $line = $stack[1];
            echo (array_key_exists("class", $line)?$line["class"]."::":"").$line["function"]."\n";
        }
        if (php_sapi_name() !== 'cli')
        {
            echo '<pre>';
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS   ,15);
            echo '</pre>';
        }else
        {
            global $argv;
            $verbose = ["-v","-vv","-vvv","--verbose"];
            $has_verbose = !empty(array_intersect($argv, $verbose));
            if($has_verbose)
            {
                $console = new \Symfony\Component\Console\Output\ConsoleOutput();
                $console->setVerbosity(\Symfony\Component\Console\Output\ConsoleOutput::VERBOSITY_DEBUG);
                (new \Symfony\Component\Console\Application())->renderException(new DD(), $console);
            //   debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS   ,15);
            }
        }

        array_map(function ($x) {
                (new Illuminate\Support\Debug\Dumper)->dump($x);
            }, func_get_args());
        exit();

        if (function_exists("xdebug_get_code_coverage"))
        {
            //xebug
            var_dump($data);
        }
        else
        {
            if (php_sapi_name() !== 'cli')
            {

                echo '<pre>';
                debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS   ,15);
                echo '</pre>';
                echo '<pre>';
                ob_start();
                var_dump($data);
                $content = ob_get_contents();
                ob_end_clean();
                echo htmlspecialchars($content,ENT_QUOTES);
                echo '</pre>';
            }
            else
            {
                debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS   ,15);
                var_dump($data);
            }

        }

        exit();
    }
}
if (!function_exists('d')) 
{
    /**
     * Display data as var_dump and kill the application
     * @param $data
     */
    function d(...$data)
    {
        $stack = debug_backtrace();
        $line = $stack[0];
        echo $line["file"].":".$line["line"]."\n";
        //if call at root
        if(count($stack)>1)
        {
            $line = $stack[1];
            echo (array_key_exists("class", $line)?$line["class"]."::":"").$line["function"]."\n";
        }
        if (php_sapi_name() !== 'cli')
        {
            echo '<pre>';
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS   ,15);
            echo '</pre>';
        }else
        {
            global $argv;
            $verbose = ["-v","-vv","-vvv","--verbose"];
            $has_verbose = !empty(array_intersect($argv, $verbose));
            if($has_verbose)
            {
                $console = new \Symfony\Component\Console\Output\ConsoleOutput();
                $console->setVerbosity(\Symfony\Component\Console\Output\ConsoleOutput::VERBOSITY_DEBUG);
                (new \Symfony\Component\Console\Application())->renderException(new DD(), $console);
            //   debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS   ,15);
            }
        }

        array_map(function ($x) {
                (new Illuminate\Support\Debug\Dumper)->dump($x);
            }, func_get_args());
    }
}
if (!function_exists('jj')) 
{
    /**
     * Display data as JSON and kill the application
     * @param $data
     */
    function jj($data)
    {
        $result = array();

        $stack = debug_backtrace();
        $line = $stack[0];
        $result["file"] = $line["file"];
        $result["line"] = $line["line"];
        $line = $stack[1];
        if(array_key_exists("class", $line))
            $result["class"] = $line["class"];
        $result["function"] = $line["function"];
        $env = array();
        if(sizeof($_POST)>0)
            $env["post"] = $_POST;
        if(sizeof($_GET)>0)
            $env["get"] = $_GET;
        if(sizeof($_FILES)>0)
            $env["files"] = $_FILES;
        if(sizeof($env)>0)
            $result["environment"] = $env;

        $result["data"] = $data;
        header('Content-Type: application/json');
        echo  json_encode($result);
        exit();
    }
}


if (!function_exists('from_camel_case')) 
{
    /**
     * Converts CamelCase string to underscore
     * @param $input CamelCase string
     * @return string
     */
    function from_camel_case($input) {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? mb_strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }
}
/**
 *    private static _tokenCars:string[]="0123456789abcdef".split("");
        private static _tokenCarsLength:number = Strings._tokenCars.length-1;
        private static _syllabus:string[] = ["par","to","mon","issu","na","bac","dat","cou","lac","son","tri","rot"];
        public static getUniqueToken(size:number = 64):string
        { 
            var count = Maths.randBetween(1, 3);
            var tokens:string[] = [];
            while(count)
            {
                tokens.push(Strings._syllabus[Maths.randBetween(0, Strings._syllabus.length-1)]);
                count--;
            }
            tokens.push(Maths.randBetween(1, 1000)+"");
            var token:string = tokens.join("-");
            token+= Date.now()+"-";
            while(token.length<size)
            {
                token+=Strings._tokenCars[Maths.randBetween(0,Strings._tokenCarsLength)];
            }
            while(token.length>size)
            {
                token = token.substring(1);
            }
            return token;
        }

 */
if (!function_exists('generate_token')) 
{
    /**
     * Generates an unique hexadecimal token
     * @param int $length Token's size
     * @return string Hexadecimal token
     */
    function generate_token($length = 64)
    {
        $cars = array("0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f");
        $cars_length = sizeof($cars);
        $syllabus = ["par","to","mon","issu","na","bac","dat","cou","lac","son","tri","rot"];

        $count = rand(1, 3);
        $tokens = [];
        while($count)
        {
            $tokens[] = $syllabus[rand(0, count($syllabus)-1)];
            $count--;
        }
        $tokens[] = rand(1, 1000);
        $token = implode("-", $tokens);
        $token.= str_replace('.','',microtime(True)."")."-";
        while(strlen($token)<$length)
        {
            $token.= $cars[rand(0, $cars_length-1)];
        }
        while(strlen($token)>$length)
        {
            $token = substr($token, 1);
        }
        return $token;
    //     var count = Maths.randBetween(1, 3);
    //     var tokens:string[] = [];
    //     while(count)
    //     {
    //         tokens.push(Strings._syllabus[Maths.randBetween(0, Strings._syllabus.length-1)]);
    //         count--;
    //     }
    //     tokens.push(Maths.randBetween(1, 1000)+"");
    //     var token:string = tokens.join("-");
    //     token+= Date.now()+"-";
    //     while(token.length<size)
    //     {
    //         token+=Strings._tokenCars[Maths.randBetween(0,Strings._tokenCarsLength)];
    //     }
    //     while(token.length>size)
    //     {
    //         token = token.substring(1);
    //     }
    //     return token;


    //     $token = str_replace('.','',microtime(True)."");
    //     while(mb_strlen($token)<$length)
    //     {
    //         $token.= $cars[rand(0, $cars_length-1)];
    //     }
    // return $token;
    }
}

if (!function_exists('timestamp')) 
{
    /**
     * Timestamp in milliseconds (1 second interval)
     * @return int
     */
    function timestamp()
    {
        return time()*1000;
    }
}
if (!function_exists('std')) 
{
    /**
     * Converts array to stdClass
     * @param $array array Will be converted to stdClass
     * @return stdClass
     */
    function std($array)
    {
        return (object) $array;
    }
}
if (!function_exists('precision')) 
{
    function precision($value, $precision, $up = true)
    {
        $value*=10**$precision;
        if($up)
        {
            $value = ceil($value);
        }else
        {
            $value = floor($value);
        }
        $value/=10**$precision;
        return round($value, $precision);
    }
}
if (!function_exists('toArray')) 
{
    /**
     * Converts StdClass to Array recursivly
     * @param $array stdClass StdClass to converts (or partial array etc)
     * @param $underscore bool Converts keys from camelCase to underscore syntax
     * @return array
     */
    function toArray($array, $underscore = False)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = toArray($value);
                }
                if ($value instanceof stdClass) {
                    $array[$key] = toArray((array)$value);
                }
            }
        }
        if ($array instanceof stdClass) {
            return toArray((array)$array);
        }
        if($underscore)
        {
            foreach($array as $key => $value)
            {
                $underscore_case = from_camel_case($key);
                if($underscore_case != $key)
                {
                    $array[$underscore_case] = $value;
                    unset($array[$key]);
                }
            }
        }

        return $array;
    }
}

if (!function_exists('is_assoc')) 
{
    /**
     * Test if an array is associative (not only number indexed)
     * @param  array  $array [description]
     * @return boolean        [description]
     */
    function is_assoc($array) {
        if(!is_array($array))
    {
            return false;
    }
    return @(bool)count(array_filter(array_keys($array), 'is_string'));
    }
}

if (!function_exists('is_numeric_array')) 
{
    /**
     * Test if an array is fully number indexed
     * @param  array  $array [description]
     * @return boolean        [description]
     */
    function is_numeric_array($array) {
    if(!is_array($array))
    {
            return false;
    }
    return @!(bool)count(array_filter(array_keys($array), 'is_string'));
    }
}
if (!function_exists('starts_with')) 
{
    /**
     * Tests if a haystack starts with a needle
     * @param $haystack
     * @param $needle
     * @return bool
     */
    function starts_with($haystack, $needle)
    {
        return $needle === "" || mb_strpos($haystack, $needle) === 0;
    }
}
if (!function_exists('ends_with')) 
{
    /**
     * Tests if a haystack ends with a needle
     * @param $haystack
     * @param $needle
     * @return bool
     */
    function ends_with($haystack, $needle)
    {
        return $needle === "" || mb_substr($haystack, -mb_strlen($needle)) === $needle;
    }
}
if (!function_exists('remove_accents')) 
{
    function remove_accents($string) {
        if ( !preg_match('/[\x80-\xff]/', $string) )
            return $string;

        $chars = array(
            // Decompositions for Latin-1 Supplement
            chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
            chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
            chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
            chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
            chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
            chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
            chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
            chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
            chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
            chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
            chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
            chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
            chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
            chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
            chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
            chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
            chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
            chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
            chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
            chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
            chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
            chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
            chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
            chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
            chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
            chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
            chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
            chr(195).chr(191) => 'y',
            // Decompositions for Latin Extended-A
            chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
            chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
            chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
            chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
            chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
            chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
            chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
            chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
            chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
            chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
            chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
            chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
            chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
            chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
            chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
            chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
            chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
            chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
            chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
            chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
            chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
            chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
            chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
            chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
            chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
            chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
            chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
            chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
            chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
            chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
            chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
            chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
            chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
            chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
            chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
            chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
            chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
            chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
            chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
            chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
            chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
            chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
            chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
            chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
            chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
            chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
            chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
            chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
            chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
            chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
            chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
            chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
            chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
            chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
            chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
            chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
            chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
            chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
            chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
            chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
            chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
            chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
            chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
            chr(197).chr(190) => 'z', chr(197).chr(191) => 's'
        );

        $string = strtr($string, $chars);

        return $string;
    }
}
if (!function_exists('join_paths')) 
{
    function join_paths() {
        $paths = array();

        foreach (func_get_args() as $arg) {
            if ($arg !== '') { $paths[] = $arg; }
        }

        return preg_replace('#/+#','/',join('/', $paths));
    }
}
if (!function_exists('cleanObject')) 
{
    function cleanObject($object, $excludes = array())
    {
        if(!is_array($object))
        {
            return $object;
        }
        foreach($object as $key=>$value)
        {
            if(in_array($key, $excludes))
            {
                continue;
            }
            if(is_numeric($key))
            {
                $object[$key] = cleanObject($object[$key], $excludes);
            }
            if($value === NULL)
            {
                unset($object[$key]);
            }else
            {
                if(is_array($value))
                {
                    $object[$key] = cleanObject($object[$key], $excludes);
                    if(sizeof($object[$key])==0 && !is_array($object[$key]))
                    {
                        unset($object[$key]);
                    }
                }
            }
        }
        return $object;
    }
}
if (!function_exists('to_array')) 
{
    /**
     * @param $object
     * @param null $init
     * @return array|mixed
     */
    function to_array($object, $init = NULL)
    {
        if(is_array($object))
        {
            foreach($object as $key => $value)
            {
                $object[$key] = to_array($value);
            }
            $data = $object;
        }else
        if(is_object($object))
        {
            if($init === $object)
            {
                //decomposition
                $keys = get_class_vars(get_class($object));
                $data = array();
                foreach($keys as $key => $value)
                {
                    if (!starts_with($key, "_")) {
                        $data[$key] = to_array($object->$key);
                    }
                }
                if( method_exists($object, "getShortName"))
                {
                    $short = "id_".$object->getShortName();
                    if(isset($data["id"]))
                    {
                        $data[$short] = $data["id"];
                    }else
                    if(isset($data[$short]))
                    {
                        $data["id"] = $data[$short];
                    }
                }
            }
            else
            {
                if(method_exists($object, "__toArray"))
                {
                    $data = to_array($object->__toArray());
                }else
                if(method_exists($object, "toArray"))
                {
                    $data = to_array($object->toArray());
                }else
                {
                    $data = json_decode(json_encode($object), True);
                }
            }

        }else
        {
            return $object;
        }
        return $data;
    }
}
if (!function_exists('recurse_copy')) 
{
    /**
     * Copy a folder to another recursively
     * @param  string $src      source folder
     * @param  string $dst      target folder
     * @param  array  $exclude  exclude file/folder or extensions (*.ext)
     * @param  string $src_root [internal]
     * @return void
     */
    function recurse_copy($src,$dst, $exclude = array(), $src_root = NULL) {
        $dir = opendir($src);
        if(!isset($src_root))
        {
            $src_root = $src;
        }
        @mkdir($dst);
     //    $this->getLogger()->info($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {

                if(__match($file, $exclude))
                {
                  //  $this->getLogger()->error($dst . '/' . $file);
                    continue;
                }


                if ( is_dir($src . '/' . $file) ) {
                    recurse_copy($src . '/' . $file,$dst . '/' . $file, $exclude, $src_root);
                }
                else {
                    copy($src . '/' . $file,$dst . '/' . $file);

                   // $this->getLogger()->normal($dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
}
if (!function_exists('__match')) 
{
    /**
     * Used by recursive_copy
     * @param  string $file    [description]
     * @param  array $exclude [description]
     * @return boolean         [description]
     */
    function __match($file, $exclude)
    {
        if(in_array($file, $exclude))
        {
            return True;
        }
        foreach($exclude as $exclusion)
        {
            if(starts_with($exclusion, "*."))
            {
                $exclusion = substr($exclusion, 1);
                if(ends_with($file, $exclusion))
                {
                    return True;
                }
            }
        }

        return False;
    }
}
if (!function_exists('method_parent_exists')) 
{
    function method_parent_exists($object,$method)
    {
        foreach(class_parents($object) as $parent)
        {
            if(method_exists($parent,$method))
            {
            return true;
            }
        }
        return false;
    }
}
if (!function_exists('is_email')) 
{
    function is_email($email){
        return filter_var($email, \FILTER_VALIDATE_EMAIL);
    }
}
if (!function_exists('rrmdir')) 
{
    /**
     * Recursive remove dir
     */
    function rrmdir($dir) { 
        if (is_dir($dir)) { 
            $objects = scandir($dir); 
            foreach ($objects as $object) { 
            if ($object != "." && $object != "..") { 
                if (is_dir($dir."/".$object))
                rrmdir($dir."/".$object);
                else
                unlink($dir."/".$object); 
            } 
            }
            rmdir($dir); 
        } 
    }
}
if (!function_exists('clean_email')) 
{
    function clean_email($email)
    {
        if(($index=strpos($email, "+")) !== False)
        {
            $index2 = strpos($email, "@");
            if($index2 !== False)
            {
                $email = substr($email, 0, $index).substr($email, $index2);
            }
        }
        return $email;
    }
}
if (!function_exists('slug')) 
{
    function slug($str, $replace=array(), $delimiter='-') {
        if(empty($str))
        {
            return "";
        }
        if( !empty($replace) ) {
            $str = str_replace((array)$replace, ' ', $str);
        }
        $str = trim($str);
        $clean = @iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        if(strlen($clean)==0)
        {
            $clean = @iconv('UTF-8', 'ASCII//IGNORE', $str);
        }
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = mb_strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
        $clean = preg_replace("/^-+/", "", $clean);
        $clean = preg_replace("/-+$/", "", $clean);
        $clean = trim($clean);
        return $clean;
    }
}
if (!function_exists('uncamel')) 
{
    function uncamel($string, $delimiter = '-')
    {
    $string = preg_replace('/(?<=\\w)(?=[A-Z])/', $delimiter. "$1", $string);
    $string = mb_strtolower($string);

    return $string;
    }
}
if (!function_exists('camel')) 
{
    function camel($string, $delimiter = '-', $replace = '')
    {
    $string = lcfirst(str_replace(' ', $replace, ucwords(str_replace($delimiter, ' ', $string))));

    return $string;
    }
}
if (!function_exists('rglob')) 
{
    function rglob($pattern, $flags = 0) 
    {
        $files = glob($pattern, $flags); 
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
            $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
        }
        return $files;
    }
}
if (!function_exists('array_orderby')) 
{
    function array_orderby()
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row)
                {
                    if (is_array($row))
                        $tmp[$key] = $row[$field];
                    else
                        $tmp[$key] = $row->$field;

                }
                $args[$n] = $tmp;
                }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }
}
if (!function_exists('split_version')) 
{
    /**
     * Extract version of a keyword
     * @param  string $name [description]
     * @return array|boolean       [description]
     */
    function split_version($name)
    {
        if(!isset($name))
        {
            return False;
        }
        $name = trim($name);
        if(is_numeric($name))
        {
            return False;
            //return ["original"=>$name,"version"=>$name, "value"=>""];
        }
        $index = -1;
        $version = "";
        $letter = NULL;
        while(True && $index >= -mb_strlen($name))
        {
            $letter = mb_substr($name, $index, 1);
            if(is_numeric($letter) || in_array($letter, [".",","]))
            {
                $version = $letter.$version;
                $index--;
            }else
            {
                break;
            }
        }
        if(strlen($version))
        {
            $value = trim(mb_substr($name, 0, mb_strlen($name)-mb_strlen($version)));
            //nb only
            if(!mb_strlen($value))
            {
                return false;
            }
            return ["original"=>$name, "version"=>$version, "value"=>$value];
        }
        return False;

    }
}
if (!function_exists('delete_directory')) 
{
    function delete_directory($path) {
        if (!file_exists($path)) {
            return true;
        }

        if (!is_dir($path)) {
            return unlink($path);
        }

        foreach (scandir($path) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!delete_directory($path . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }

        }

        return rmdir($path);
    }
}
if (!function_exists('smart_merge')) 
{
    function smart_merge($array1, $array2)
    {
        /** @var Config $value */
        foreach ($array2 as $key => $value) {
            if(is_int($key))
            {
                $array1[] = $value;
            }else
            {
                if(!isset($array1[$key]))
                {
                    $array1[$key] = $value;
                }else
                {
                    if(is_array($value))
                    {
                        $array1[$key] = smart_merge($array1[$key], $value);
                    }
                }
            }
        }
        return $array1;
    }
}
if (!function_exists('get_files')) 
{
    function get_files($path, $recursive = False)
    {
        if($recursive)
        {
            try
            {
                $objects = new 	\RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::SELF_FIRST);
                
            }catch(\Exception $e)
            {
                $objects = [];
            }
            $files = [$path];
            foreach($objects as $name => $object){
                if(substr($name, -1) == ".")
                    continue;
                $files[] = $name;
            }
            return $files;
        }
    }
}
if (!function_exists('file_right')) 
{
    function file_right($path, $recursive = False)
    {
        if(!file_exists($path))
            return 0;
        if($recursive)
        {
            $files = get_files($path, $recursive);
        }else
        {
            $files = [$path];
        }
        return min(array_map(function($item)
        {
            return (int)substr(sprintf('%o', fileperms($item)), -4);
        }, $files));
    }
}
if (!function_exists('clean_array')) 
{
    function clean_array($data)
    {
        return array_filter($data, function($item)
        {
            return $item !== NULL;
        });
    }
}
if (!function_exists('array_transpose')) 
{
    function array_transpose($array) {
        return array_map(null, ...$array);
    }
}
if (PHP_VERSION_ID < 70100) {
     if (!function_exists('is_iterable')) {
            function is_iterable($var) { 
                return is_array($var) || $var instanceof \Traversable;
             }
        }
    }
}


