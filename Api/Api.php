<?php
namespace Core\Api;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use File;
use Route;
use Request;
use Auth;
use Core\Exception\ApiException;
use Core\Exception\Exception;
use App;
class Api
{
    public static $data = [[]];

    public static function addAPIData($data)
    {
        $len = count(static::$data)-1;
        $previous = static::$data[$len];
        static::$data[$len] = static::array_merge($previous, $data);
    }
    public static function popAPIData()
    {
        return array_pop(static::$data);
    }
    protected static function array_merge($array1, $array2)
    {
        if(is_numeric_array($array2))
        {
            return $array2;
        }
        foreach($array2 as $key=> $value)
        {
            if(is_array($value))
            {
                if(isset($array1[$key]) && is_array($array1[$key]))
                {
                    $array1[$key] = static::array_merge($array1[$key], $array2[$key]);
                    continue;
                }
            }
            $array1[$key] = $array2[$key];
        }
        return $array1;
    }

    protected $path;
    protected $method;
    protected $params;
    protected $api_user;
	public function __construct()
	{

	}
    public function isMainCall()
    {
        return count(static::$data) == 1;
    }
    public function get($path)
    {
        $this->path = $path;
        $this->method = "GET";
        return $this;
    }
    public function post($path)
    {
        $this->path = $path;
        $this->method = "POST";
        return $this;
    }
    public function params($params)
    {
        $this->params = $params;
        return $this;
    }
    public function param($name, $value)
    {
        if(!isset($this->params))
        {
            $this->params = [];
        }
        $this->params[$name] = $value;
        return $this;
    }
    public function user($user)
    {
        $this->api_user = $user;
        return $this;
    }
    protected function dispatching($params = NULL)
    {
         $temp = Request::input();

        $request = Request::create($this->path, $this->method);
        if(isset($this->params))
        {
            foreach($this->params as $key=>$value)
            {
                $request->query->set($key, $value);
            }
        }
        if(isset($params))
        {
            foreach($params as $key=>$value)
            {
                $request->query->set($key, $value);
            }
        }

        //inputs
        if(isset($this->api_user))
        {
            $temp_user = Auth::getUser();
            Auth::setUser($this->api_user);
        }
        Request::replace($request->input());
        if(App::runningInConsole())
        {
            $rawresponse = app()['Illuminate\Contracts\Http\Kernel']->handle($request);
        }else
        {
            $rawresponse = Route::dispatch($request);
        }
        Request::replace($temp);
        if(isset($temp_user))
        {
            Auth::setUser($temp_user);
        }
        return $rawresponse;
    }
    public function response($params = NULL)
    {
        static::$data[] = [];
        $rawresponse = $this->dispatching($params);
        $api_data = static::popAPIData();
        $rawresponse = $rawresponse->getOriginalContent();
        $response = new ApiResponse();
        if(isset($rawresponse["data"]))
            $response->value = $rawresponse["data"];
        if(isset($rawresponse["stats"]))
            $response->stats = $rawresponse["stats"];
        if(isset($rawresponse["exception"]))
            $response->exception = $rawresponse["exception"];
        $response->apiData = $api_data;
        return $response;
    }
    public function send($params = NULL)
    {
        $response = $this->response($params);
        if($response->isException())
        {
            $exception = $response->getNewException();
            throw $exception;
        }
        return $response->value;
    }


    public function unserialize($data)
    {
        $object = json_decode(base64_decode($data), True);
        if(isset($object["cls"]))
        {
            $instance = new $object["cls"];
            $instance->unserialize($object["data"]);
            return $instance;


        }
        return $object;
    }
	protected function generateRoutes()
	{
		$folder = __DIR__.'/Annotations';

		$files = File::allFiles($folder);
		foreach ($files as $file)
		{
       		AnnotationRegistry::registerFile($file->getPathname());
		}

        $annotationReader = new AnnotationReader();

        $namespace = '\App\Http\Controllers\\';

        $root = base_path();
        $folder = join_paths($root, "app/Http/Controllers");
        $files = File::allFiles($folder);
       

        foreach ($files as $file)
        {
            $infos = pathinfo($file);
            if($infos["extension"] != "php")
                continue;
            
            $methodAnnotations = [];
            $classAnnotations  = [];

            $current_folder = substr($infos["dirname"], strlen($folder)+1);

            $current_namespace = $namespace;

            $prefix_class = str_replace('/','\\', $current_folder);
            if(strlen($prefix_class))
            {
                $prefix_class.='\\';
            }

            $class = $current_namespace.$prefix_class.$infos["filename"];
            $className = $prefix_class.$infos["filename"];
           // var_dump("dir:".$current_namespace.$className);
            $reflectedClass = new \ReflectionClass($current_namespace.$className);
            $classAnnotations = $annotationReader->getClassAnnotations($reflectedClass);

            $methods = $reflectedClass->getMethods(\ReflectionMethod::IS_PUBLIC);
            foreach($methods as $method)
            {
                $reflectedMethod = new \ReflectionMethod($class, $method->name);
                $annotations = $annotationReader->getMethodAnnotations($reflectedMethod);
                if(!empty($annotations))
                {
                    $methodAnnotations[$method->name] = $annotations;
                }
            }
            if(!empty($classAnnotations) || !empty($methodAnnotations))
            {
                $path = strtolower(str_ireplace('\\','/',str_ireplace("controller", "", $className)))."/";
                $middlewares = [];


                //prepare class annotations
                foreach($classAnnotations as $annotation)
                {
                    $annotation->setIsFromClass(true);
                    $annotation->boot();
                }

                foreach($methodAnnotations as $methodName=>$annotations)
                {
                    foreach($classAnnotations as $annotation)
                    {
                       $annotation->handleAnnotations($annotations);
                    } 
                    $config = new \StdClass();
                    $config->middlewares = [];
                    $config->path = $path.uncamel($methodName);
                    $config->route = $className.'@'.$methodName;

                    //method annotations
                    foreach($annotations as $annotation)
                    {
                       $annotation->boot();
                       $annotation->handle($config);
                    } 

                    $route = Route::get($config->path, $config->route);
                    foreach($config->middlewares as $middleware)
                    {
                        $route->middleware($middleware);
                    }

                }
            }
        }
	}
    public function handle($params)
    {
        throw new \Exception('pik');
        //dd($params);
    }
	 /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

}
