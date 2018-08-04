<?php
namespace Core\Api\Annotations;
use Core\Exception\Exception;
use Core\Exception\ApiException;


class ParamObject extends CoreObject implements IMetaObject
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var mixed
     */
    public $value;

    public function hasData()
    {
        return isset($this->name) || isset($this->value);
    }
    public function exchangeRequest($data)
    {
        if(isset($data["params"][$this->name]))
        {
            $this->value = $data["params"][$this->name];
        }
    }
    public function getAPIKey()
    {
        return "params";
    }
    public function getAPIObject()
    {
        return new ParamClass();
    }
}
class ParamClass
{
    public function toArray(...$args)
    {
        $all = empty($args);
        $keys = [];
        foreach($args as $key=>$value)
        {
            if(is_array($value))
            {
                $keys = array_merge($keys, $value);
            }else
            {
                $keys[] = $value;
            }
        }
        $data = [];
        foreach($this as $key=>$value)
        {
            if($all || in_array($key, $keys))
            {
                $data[$key] = $value->value;
            }
        }
        return $data;
    }
    public function toArrayClean(...$args)
    {
        $result = call_user_func_array([$this, "toArray"],$args);
        foreach($result as $key=>$value)
        {
            if($value === NULL)
            {
                unset($result[$key]);
            }
        }
        return $result;
    }
    public function set($name, $value)
    {
        if(!isset($this->$name))
        {
            $this->$name = new ParamObject();
            $this->$name->name = $name;
        }
        $this->$name->value = $value;
        return $this;
    }

    public function remove($name)
    {
        if(!isset($this->$name))
        {
            unset($this->$name);
        }
        return $this;
    }
}
/**
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class Param extends CoreAnnotation
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $requirements = null;
    /**
     * @var boolean
     */
    public $required = false;
    /**
     * @var boolean
     */
    public $is_console = false;
    /**
     * @var boolean
     */
    public $array = false;
    /**
     * @var boolean flag missing model when using type
     */
    public $flag_missing = True;
    /**
     * @var mixed
     */
    public $value;
    /**
     * variable type
     * @var string
     */
    public $type;
    /**
     * @var mixed
     */
    public $default;
    /**
     * @var mixed option for type=class
     */
    public $option;
    /**
     * @var mixed option for type=class
     */
    public $prop;
    /**
     * @param $value
     * @param $request
     * @return mixed
     * @throws Exception
     */

    protected function _parse($value, $request)
    {
        $object = parent::_parse($value, $request);
        //get casted value
//        $object->value = $this->validate( $object->value );
        $this->_key = $object->name;
        return $object;
    }

    protected function isConsole()
    {
        if (php_sapi_name() !== 'cli')
            return false;

        return true;
    }

    public function validate( $value )
    {
        if (true === $this->required && null === $value)
            throw new ApiException($this->name . " is required", 10);

        // check if param if required
        if (null !== $this->requirements && isset($value) && true === $this->array && false === is_array($value))
            throw new ApiException($this->name . " must be an array.", 10);

        // check if param if required
        if (null !== $this->requirements  && isset($value) && false === $this->array && true === is_array($value))
            throw new ApiException($this->name . " musn't be an array.", 10);

        if (null !== $value && true === $this->is_console && !$this->isConsole() && $this->api->isFromFront())
            throw new ApiException($this->name . " need to be set in console.", 10);

        // check requirements (regex)
        if (null !== $this->requirements && null !== $value)
        {
            $data = (false === $this->array) ? [ $value ] : $value;

            foreach ($data as $k=>$d)
            {
                if($this->requirements == "timestamp")
                {
                    $this->type = 'int';
                    if(!is_timestamp($d))
                    {
                        $result = strtotime($d);
                        if($result === False)
                        {
                            throw new ApiException($this->name." must be a timestamp");
                        }
                        $data[$k] = $d = strtotime($d);
                    }
                }else
                if($this->requirements == "boolean")
                {
                    if($d == "true")
                    {
                        //cast $data for the paramObject
                        $data[$k] = $d = True;
                    }else
                    if($d == "false")
                    {
                        //cast $data for the paramObject
                        $data[$k] = $d = False;
                    }
                    if($d == 1)
                    {
                         $data[$k] = $d = True;
                    }
                    if($d == 0)
                    {
                        $data[$k] = $d = False;
                    }
                    if(!is_bool($d))
                    {
                        throw new ApiException($this->name . " should be boolean", 10);
                    }
                }
                else
                if($this->requirements == "email")
                {
                   if(!is_email($d))
                   {
                      throw new ApiException($this->name . " must be an email format", 10);
                   }
                }
                else
                if (preg_match('/^' . $this->requirements . '$/', $d) === 0)
                    throw new ApiException($this->name . " requirements syntax error : " . $this->requirements, 10);
            }
            return (false === $this->array) ? $data[0]:$value;
            // $object->value = (false === $this->array) ? $data[0]:$value;
            // return $object;//return  (false === $this->array) ? $data[0]:$value;
        }else if($value !== null)
        {
            if($this->array && !is_array($value))
            {
                $value = [$value];
            }
        }
        
        return $value;
    }
}
