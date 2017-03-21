<?php

namespace Core\Console\Commands\Doc;
use Db;
use Core\Console\Commands\CoreCommand;
use Core\Util\ClassWriter;
use Core\Util\ClassWriter\Body\Table;
use Core\Util\ClassWriter\Body\General;
use Schema;
use ReflectionClass;
use File;
use Logger;
use Core\Util\Command;
use App;
use Route;
use Core\Util\MarkdownWriter;
use Core\Util\Command as ExecCommand;
use Core\Util\ClassHelper;
use Tables\STATS_API_CALL;
use stdClass;
use Api;
use Core\Api\Annotations\Paginate;
use Core\Api\Annotations\Param;
use Core\Api\Annotations\Role;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Core\Util\ModuleHelper;
class Generate extends CoreCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'doc:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate doc slate';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $routes = Route::getRoutes()->getRoutes();//sortBy('uri');
        usort($routes, function($a, $b)
        {
            return $a->uri <=> $b->uri;
        });


        $path = config('doc.path');
        $doc = new MarkdownWriter();
        $doc->data("title", "API usage");
        $doc->data("language_tabs",["php","json"]);
        $doc->data("search",true);
        //$doc->data("language_tabs","php");
        $done = [];
        foreach($routes as $route)
        {
            $parts = explode("/",$route->uri);
            foreach($parts as $key=>$part)
            {
                $part = join("/",array_slice($parts, 0, $key+1));
                if($key && $key+1 < count($parts))
                {
                    continue;
                }
                if(!in_array($part, $done))
                {
                    $doc->title($part, $key?2:1/*$key+1*/);
                    $done[] = $part;
                }
            }



           


            $stats = STATS_API_CALL::where('path','=',$route->uri)
            ->select(Db::raw('COUNT(*) as count, COUNT(DISTINCT id_user) as id_user'))
            ->first();

            $doc->annotation_right('Called **'.$stats->count.'** time'.($stats->count!=1?'s':'')." by **".$stats->id_user."** loggued user".($stats->id_user!=1?'s':''));

             //get exemple of call & result 
            $exemple = STATS_API_CALL::where('path','=',$route->uri)
            ->where('value','not like','{"data":null%')
            ->orderBy('created_time','desc')->first();
            if(isset($exemple))
            {
                $json = $exemple->value;
                $json = json_decode($json);
                $json = $json->data;
                $doc->code('json', $json);  
                $params = json_decode($exemple->params??"[]", True);
                if(isset($params["api_token"]))
                {
                    unset($params["api_token"]);
                }
                if(empty($params))
                {
                    $doc->code('php', "<?\nApi::path('".$route->uri."')->send();");    
                }else
                {
                    $doc->code('php', "<?\nApi::path('".$route->uri."')->send(".var_export($params, True).");");    
                }
            }else
            {
                $doc->code('php', "<?\nApi::path('".$route->uri."')->send();");                      
            }
            $doc->code('php', "<?\n".preg_replace("/^    /m", "", ClassHelper::getMethodBody($route->action["uses"], True)));                      

            $doc->aside($route->action["uses"]);     
            //comments
            $docData = $this->getDocData($route->action["uses"]);
            if(isset($docData->text))
            {
                $doc->title("Description", 3);
                $doc->text(str_replace("\n","\n\n",$docData->text));
            }
            if(!empty($docData->success))
            {
                foreach($docData->success as $text)
                {
                    $doc->aside($text, "success");
                }
            }
            if(!empty($docData->notice))
            {
                foreach($docData->notice as $text)
                {
                    $doc->aside($text, "notice");
                }
            }
            if(!empty($docData->warning))
            {
                foreach($docData->warning as $text)
                {
                    $doc->aside($text, "warning");
                }
            }
              //middlewares
             if(count($route->action["middleware"])>1)
             {
                $middlewares = array_map(function($item)
                    {
                        list($class, $param) = explode(":", $item);
                        $middleware = new stdClass();
                        $middleware->class = $class;
                        if(isset($param))
                            $instance = Api::unserialize($param); 
                        $middleware->instance = $instance;
                        return $middleware;
                    }, array_values(array_filter($route->action["middleware"], function($item){return $item!="api";})));
                
                //show middlewares
                $doc->title("Middlewares", 3);
                $doc->table(["class"=>array_map(function($item)
                    {
                        return $item->class;
                    },$middlewares)]);      


                $params = array_values(array_filter($middlewares, function($item)
                {
                    return isset($item->instance) && $item->instance instanceof Param;
                }));
                $paginate = array_values(array_filter($middlewares, function($item)
                {
                    return isset($item->instance) && $item->instance instanceof Paginate;
                }));
                $roles = array_values(array_filter($middlewares, function($item)
                {
                    return isset($item->instance) && $item->instance instanceof Role;
                }));
                if(!empty($roles))
                {
                    $role = $roles[0]->instance;
                    $doc->title("Roles", 3);
                    $doc->table(
                    [
                        "base"=>$role->roles,
                        "needed"=>$role->getNeeded(),
                        "forbidden"=>$role->getForbidden()

                    ]);    
                }     

                if(!empty($paginate))
                {
                    $param = new stdClass();
                    $param->name = "paginate";
                    $param->requirements = "api syntax";
                    $param->default = $paginate[0]->instance->keys[0];
                    $param->required = "false";
                    $param->array = "true";
                    $param->type = "-";

                    $temp = new stdClass();
                    $temp->instance = $param;
                    $params[] = $temp;
                }


                //show params list
                if(!empty($params))
                {
                    $doc->title("Parameters", 3);
                    $doc->table(["name"=>array_map(function($item)
                    {
                        return "**`".$item->instance->name."`**";
                    },$params),
                    "requirements" => array_map(function($item)
                    {
                        return $item->instance->requirements;
                    },$params),
                     "type" => array_map(function($item)
                    {
                        return $item->instance->type;
                    },$params),
                    "default" => array_map(function($item)
                    {
                        return $item->instance->default;
                    },$params),
                    "required" => array_map(function($item)
                    {
                        return $item->instance->required == "true" || $item->instance->required === true?'**true**':$item->instance->required;
                    },$params),
                    "array" => array_map(function($item)
                    {
                        return $item->instance->array;
                    },$params)

                    ]);    
                }      

               
                if(!empty($paginate))
                {
                    $paginate = $paginate[0]->instance;
                    $doc->title("Paginate", 3);
                    $doc->table(
                    [
                        "allowed"=>$paginate->allowed,
                        "default"=>array_map(function($item) use($paginate)
                        {
                            return in_array($item, $paginate->keys)?(count($paginate->keys)==1?'✔':array_search($item, $paginate->keys)+1):' ';

                        }, $paginate->allowed),
                        "directions"=>array_map(function($item) use($paginate)
                        {

                            if(count($paginate->directions)>$item)
                            {
                                $direction = $paginate->directions[$item];
                            }else
                            {
                                $direction = $paginate->directions[count($paginate->directions)-1];
                            }
                            if($direction == 1)
                            {
                                return "ASC";
                            }
                            return "DESC";
                        }, array_keys($paginate->allowed))

                    ]);    
                }          
             }
            if(isset($docData->return))
            {
                $doc->title("Return", 3);
                $doc->lightcode($docData->return);
            }
        }
        $path = join_paths($path, "index.html.md");
        $old = file_exists($path)?file_get_contents($path):NULL;
        $new = $doc->getOutput();
        if($old != $new)
        {
            Logger::info('Documentation: update needed');
            file_put_contents($path, $new);
            chdir(base_path('docs'));
            ExecCommand::execute('bundle', ["exec", "middleman", "build", "--clean"]);
            chdir(base_path());
        }else
        {
            Logger::info('Documentation: no change');
        }
    }
    protected function getDocData($path)
    {
        list($classname, $methodname) = explode("@", $path);

        //doc
        $reflectedMethod = new \ReflectionMethod($classname, $methodname);
        $docs = $reflectedMethod->getDocComment();
        $docs = str_replace("/**", "", $docs);
        $docs = str_replace("*/", "", $docs);
        $docs = preg_replace("/^( |\t)*\*/m", "", $docs);

        $authorized = ["return", "notice","success","warning"];

        $docs = array_values(array_filter(array_map(function($item){return trim($item);},explode("\n", trim($docs))), function($item) use($authorized){

            if(!strlen($item))
                return False;
            if(mb_substr($item, 0, 1) == "@")
            {
                $name = explode(" ",$item)[0];
                $name = mb_substr($name, 1);
                if(in_array($name, $authorized))
                    return true;
                return false;
            }
            return true;
        }));
        $text = "";
        $return = [];
        $notice = [];
        $success = [];
        $warning = [];
        foreach($docs as $doc)
        {
            if(mb_substr($doc, 0, 1) != "@")
            {
                $text.= $doc."\n";
            }else
            {
                $name = explode(" ",$doc)[0];
                $name = mb_substr($name, 1);
                $$name[] = trim(mb_substr($doc, mb_strlen($name)+2));
            }
        }
        $result = new stdClass();
        $result->text = mb_strlen($text)?$text:NULL;
        $result->return = isset($return)?join(" ",$return):NULL;
        $result->notice = $notice??NULL;
        $result->warning = $warning??NULL;
        $result->success = $success??NULL;
        return $result;
    }
}
