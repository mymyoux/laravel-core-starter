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
use Job;
use Core\Jobs\Api as ApiJob;
use Logger;
use Core\Util\ClassHelper;
use Illuminate\Http\JsonResponse;
use Core\Util\ModuleHelper;
use App\User;
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
    public static function getAllAPIDATA()
    {
        //debug only
        return static::$data;
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
        return count(static::$data) == 0;
    }
    public function has($path)
    {
        return Route::has($path);
    }
    public function get($path)
    {
        $this->path = $path;
        $this->method = "GET";
        return $this;
    }
    public function path($path)
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
    public function method($method)
    {
        $this->method = $method;
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
    public function admin()
    {
        $user = User::getConsoleUser( 'admin' );
        $this->api_user = $user;
        return $this;
    }
    protected function dispatching($params = NULL)
    {
        $temp = Request::all();
        $prefix = config('api.prefix') ? config('api.prefix') . '/': '';

        $request = Request::create($prefix . $this->path, $this->method);
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
            if(is_numeric($this->api_user))
            {
                Auth::loginUsingId($this->api_user);
            }else
            {
                Auth::setUser($this->api_user);
            }
        }

        Request::replace($request->all());
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
        //$api_data = static::popAPIData();
        $rawresponse = $rawresponse->getOriginalContent();
        
        if($rawresponse instanceof JsonResponse)
        {
            $rawresponse = $rawresponse->getData(True);
        }
        $api_data = isset($rawresponse["api_data"])?$rawresponse["api_data"]:NULL;
        //var_dump($api_data);
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
    public function queue($params = NULL)
    {
        $data = [];
        foreach($this as $key=>$value)
        {
            $data[$key] = $value;
        }
        if(isset($data["api_user"]) && !is_numeric($data["api_user"]))
        {
            $data["api_user"] = $this->api_user->id_user;
        }
        $data["add_params"] = $params;
        return Job::create(ApiJob::class, clean_array($data))->send();
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
    protected function searchControllersFolder($path)
    {
        $folders = File::directories($path);
        $good = [];
        foreach($folders as $folder)
        {
            $parts = explode("/", $folder);
            if(array_last($parts) == "Controllers")
            {
                $good[] = $folder;
            }else
            {
                $good = array_merge($good, $this->searchControllersFolder($folder));
            }
        }
        return $good;
    }
    public function registerAnnotations()
    {
        //$folder = __DIR__.'/Annotations';

        $paths = array_map(function($item)
        {
            return $item["path"];
        },ModuleHelper::getModulesFromComposer());
        
        foreach($paths as $path)
        {
            $folder = base_path(join_paths($path, "Annotations"));
            if(!file_exists($folder))
            {
                $folder = base_path(join_paths($path, "Api","Annotations"));
            }
            if(file_exists($folder))
            {
                $files = File::allFiles($folder);
                foreach ($files as $file)
                {
                    AnnotationRegistry::registerFile($file->getPathname());
                }
            }
        }


    }
	protected function generateRoutes()
	{
		$this->registerAnnotations();

        $annotationReader = new AnnotationReader();
        $annotationReader::addGlobalIgnoredName('notice');
        $annotationReader::addGlobalIgnoredName('warning');
        $annotationReader::addGlobalIgnoredName('success');


        $paths = config('api.modules');
        $folders = [];
         foreach($paths as $path)
        {
            $folders = array_merge($folders, $this->searchControllersFolder($path));
        }

        $root = base_path();
        $paths = [];

        foreach($folders as $folder)
        {

            // $folder = join_paths($root, "app/Http/Controllers");
            $files = File::allFiles($folder);


            foreach ($files as $file)
            {
                $infos = pathinfo($file);
                if($infos["extension"] != "php")
                    continue;


                $class_data = ClassHelper::getInformations($file);


                $methodAnnotations = [];
                $classAnnotations  = [];

                $current_folder = substr($infos["dirname"], strlen($folder)+1);

                // $current_namespace = $namespace;

                // $prefix_class = str_replace('/','\\', $current_folder);
                // if(strlen($prefix_class))
                // {
                //     $prefix_class.='\\';
                // }

                $class = '\\'.$class_data->fullname;//$current_namespace.$prefix_class.$infos["filename"];
                $index = strpos($class, 'Controllers');
                // $className = $prefix_class.$infos["filename"];
                //keep only namespace after Controllers
                $className = substr($class, $index+12);
                $current_namespace = substr($class,0, $index+12);
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
                        foreach($classAnnotations as $annotation)
                        {
                           if(!$annotation->hasBeenHandled())
                           {
                                $annotations[] = $annotation;
                           }
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
                        if(in_array($config->path, $paths))
                        {
                            //Logger::warn('ignore '.$config->path.' from '.$className.'@'.$methodName);
                            break;
                        }
                        $route = Route::match(['get', 'post'], $config->path, $current_namespace.$config->route)->name($config->path);
                        $paths[] = $config->path;
                        foreach($config->middlewares as $middleware)
                        {
                            $route->middleware($middleware);
                        }

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
