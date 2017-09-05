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
    protected $doc;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'doc:generate {--ts=0} {--php=0}';

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
        $generate_php = True;
        $generate_ts = True;
        if($this->option('php') || $this->option('ts'))
        {
            if(!$this->option('php'))
                $generate_php = False;
            if(!$this->option('ts'))
                $generate_ts = False;
        }

        if($generate_php)
        {
        //   $this->generatePHP(); 
            $this->generatePHPAPI(); 
        }
     
        if($generate_ts)
        {
           //$this->generateTS(); 
        }
     
        
    }
    protected function createDoc($title)
    {
        $doc = new MarkdownWriter();
        $doc->data("title", "API Typescript - ".$title);
        $doc->data("language_tabs",["typescript"]);
        $doc->data("search",true);
        return $doc;
    }
    protected function generateTS()
    {
        ini_set('memory_limit', '-1');
        chdir(resource_path('assets/ts'));

        $json_path = public_path('docs.json');//join_paths(base_path(config('doc.typescript.path')),"source","doc.json");
        //js
        ExecCommand::executeRaw("typedoc", ["--json",$json_path,"--mode", "modules"]);

        $json = json_decode(file_get_contents($json_path));

        $docs = [];


        //index each files
        foreach($json->children as &$child)
        {
            if(!isset($child->children ) || !isset($child->originalName))
            {
                continue;
            }
            
            $child->content = explode("\n",file_get_contents($child->originalName));
            $child->lines = [];

            $child->key = explode("/",substr($child->name, 1))[0];
            if($child->key == "framework")
            {
                $child->key = explode("/",substr($child->name, 1))[1];
            }
            if(!isset($docs[$child->key]))
            {
                $docs[$child->key] = $this->createDoc($child->key);
            }
            $child->doc = $docs[$child->key];

            foreach($child->sources as $source)
            {
                if($source->fileName == substr($child->name, 1, strlen($child->name)-2).".ts")
                {
                    
                    $child->lines[] = $source->line;
                }
            }
            foreach($child->children as &$class)
            {
                foreach($class->sources as $source)
                {
                    if($source->fileName == substr($child->name, 1, strlen($child->name)-2).".ts")
                    {
                        $child->lines[] = $source->line;
                    }
                }
                if(!isset($class->flags->isExported ))
                {
                    continue;
                }
                if(!isset($class->children))
                    continue;

                // usort($class->children, function($a, $b) use($child)
                // {
                //     $aline = 9999999;
                //     $bline = 9999999;
                //     foreach($a->sources as $source)
                //     {
                //         if($source->fileName == substr($child->name, 1, strlen($child->name)-2).".ts")
                //         {
                //             if($aline>$source->line)
                //             {
                //                 $aline = $source->line;
                //             }
                //         }
                //     }
                //     foreach($b->sources as $source)
                //     {
                //         if($source->fileName == substr($child->name, 1, strlen($child->name)-2).".ts")
                //         {
                //             if($bline>$source->line)
                //             {
                //                 $bline = $source->line;
                //             }
                //         }
                //     }
                //     return $aline - $bline;
                // });

                    //get lines
                foreach($class->children as $property)
                {
                    foreach($property->sources as $source)
                    {
                        if($source->fileName == substr($child->name, 1, strlen($child->name)-2).".ts")
                        {
                            
                            $child->lines[] = $source->line;
                        }
                    }
                }
                
                usort($class->children, function($a, $b)
                {
                    if($a->kindString == "Property")
                    {
                        $av = 0;
                    }else if($a->kindString != "Property")
                    {
                        $av = 1;
                    }
                    if($b->kindString == "Property")
                    {
                        $bv = 0;
                    }else if($b->kindString != "Property")
                    {
                        $bv = 1;
                    }
                    if($av != $bv)
                        return $av-$bv;
                    if(isset($a->flags->isPublic))
                    {
                        $av = 1;
                    }elseif(isset($a->flags->isProtected))
                    {
                        $av = 2;
                    }elseif(isset($a->flags->isPrivate))
                    {
                        $av = 3;
                    }
                    
                    if(isset($a->flags->isStatic))
                    {
                        $av -= -10;
                    }
                    if(isset($b->flags->isPublic))
                    {
                        $bv = 1;
                    }elseif(isset($b->flags->isProtected))
                    {
                        $bv = 2;
                    }elseif(isset($b->flags->isPrivate))
                    {
                        $bv = 3;
                    }
                    
                    if(isset($b->flags->isStatic))
                    {
                        $bv -= -10;
                    }
                    return $av-$bv;
                });
            }
            sort($child->lines);
        }
        foreach($json->children as $child)
        {
            if(!isset($child->children ) || !isset($child->originalName))
            {
                continue;
            }
            $index = strpos( $child->name, $child->key)+strlen($child->key)+1;
            $child->doc->title(substr(substr($child->name, 0, strlen($child->name)-1),$index), 1);
            $this->displayComments($child, $child, 2);
            
            if(!isset($child->children ) || !isset($child->originalName))
            {
                continue;
            }
            foreach($child->children as $class)
            {
                if(!isset($class->flags->isExported ))
                {
                    continue;
                }
                if($class->flags->isExported === true)
                {
                   $child->doc->title($class->name,2);
                   $this->displayComments($child, $class, 3);
                   if(!empty($class->extendedTypes))
                   {
                        $child->doc->title("extends ",3);
                        foreach($class->extendedTypes as $extend)
                        {
                            if(isset($extend->name))
                            {
                                $child->doc->html('<a href="#'.strtolower($extend->name).'">'.$extend->name.'</a>');
                            }else
                            {
                                var_dump($extend);
                            }
                        }
                   }
                }
                if(!isset($class->children))
                    continue;

                foreach($class->children as $property)
                {
                    $classes = ["inherits"];
                    $line = 0;
                    foreach($property->sources as $source)
                    {
                        $line = $source->line;
                        if($source->fileName == substr($child->name, 1, strlen($child->name)-2).".ts")
                        {
                            
                            $classes = [];
                            break;
                        }
                    }
                    if(isset($property->flags->isStatic) && $property->flags->isStatic)
                    {
                        $classes[] = "static";
                    }
                    if(isset($property->flags->isPublic) && $property->flags->isPublic)
                    {
                        $classes[] = "public";
                    }
                    if(isset($property->flags->isProtected) && $property->flags->isProtected)
                    {
                        $classes[] = "protected";
                    }
                     if(isset($property->flags->isPrivate) && $property->flags->isPrivate)
                    {
                        $classes[] = "private";
                    }
                    if(in_array("inherits", $classes))
                    {
                        // $child->doc->html('<p class="inherits"></p>');
                        // $child->doc->title($property->name.":".$property->kindString,3);
                    }else
                    {
                        if(!empty($classes))
                        {
                            $child->doc->html('<p class="'.join($classes, " ").'"></p>');   
                        }
                        $type = $property->type->name??"any";
                        if(!empty($property->signatures))
                        {
                            $type = $property->signatures[0]->type->name??"any";
                        }
                        // if(!in_array($type,["boolean","void","string","number"]))
                        // {
                        //     $type = '<a href="#'.strtolower($type).'">'.$type.'</a>';
                        // }
                        if(isset($property->defaultValue))
                        {
                            
                        }
                        $child->doc->title($property->name.":`".$type."`".(isset($property->defaultValue)?' = '.$property->defaultValue:''),3);
                        
                        if($property->kindString != "Property")
                        {
                            $index = array_search($line, $child->lines);
                            $until = count($child->lines)>$index+1?$child->lines[$index+1]-1:count($child->content);
                            $line--;
                            if($until>$line){
                                $code = /*"//line $property->name $line : $until\n".*/join("\n",array_slice($child->content, $line, $until-$line));
                                $child->doc->code('typescript', $code);  
                            }
                        }
                        if(isset($property->signatures))
                        {
                            foreach($property->signatures as $signature)
                            {
                                
                                if(!empty($signature->parameters))
                                {
                                    $child->doc->table(["parameters"=>array_map(function($item)
                                    {
                                        return "**`".$item->name."`**";
                                    }, $signature->parameters),
                                    "type"=>array_map(function($item)
                                    {
                                        return $item->type->name??"any";
                                    }, $signature->parameters),
                                    "description"=>array_map(function($item)
                                    {
                                        return $item->comment->text??"";
                                    }, $signature->parameters)
                                    ]);
                                }
                                $this->displayComments($child, $signature, 4);
                            }
                        } 

                        $this->displayComments($child, $property, 4);

                        

                       
                        

                    }
                }
                //$child->doc->text(str_replace("\n","\n\n",$child->docData->text));
            }
        }
        foreach($docs as $key=>$doc)
        {
            $output = $doc->getOutput();
            $path = join_paths(config('doc.typescript.path'), "source",$key.".html.md");
            Logger::info("writing ".$path);
            file_put_contents($path, $output);
        }
        chdir(config('doc.typescript.path'));
        ExecCommand::execute('bundle', ["exec", "middleman", "build", "--clean"]);
        chdir(base_path());
        

        // $path = join_paths(config('doc.typescript.path'), "source","index.html.md");
        // file_put_contents($path, $output);
        // chdir(config('doc.typescript.path'));
        // ExecCommand::execute('bundle', ["exec", "middleman", "build", "--clean"]);
        // chdir(base_path());
    }
    protected function hasComment($property)
    {
        if(isset($property->comment))
        {
            if(isset($property->comment->shortText))
            {
                return true;
            }
            if(isset($property->comment->tags))
            {
                foreach($property->comment->tags as $tag)
                {
                    if(in_array($tag->tag, ["notice","error","warning"]))
                    {
                       // $doc->aside($tag->text, $tag->tag);
                    }else {
                        return true;
                    }
                }
            }
            
        }
        if(!isset($property->signatures))
            return False;
         foreach($property->signatures as $signature)
        {
            
            if(!empty($signature->parameters))
            {
                return true;
            }
            if($this->hasComment($signature))
                return true;
        }
        return False;
    }
    protected function displayComments($child, $property, $level)
    {
        $doc = $child->doc;
         if(isset($property->comment))
        {
            if(isset($property->comment->shortText))
            {
                $doc->title('description', $level);
                $doc->text($property->comment->shortText);
            }
            if(isset($property->comment->tags))
            {
                foreach($property->comment->tags as $tag)
                {
                    if(in_array($tag->tag, ["notice","error","warning"]))
                    {
                        $doc->aside($tag->text, $tag->tag);
                    }else {
                        $doc->title($tag->tag, $level);
                        $doc->text($tag->text);
                    }
                }
            }
        }
    }
    protected function generatePHP()
    {
        $annotationReader = new AnnotationReader();

            $modules = ModuleHelper::getModulesFromComposer();
            $modules = array_reverse($modules);
            foreach($modules as $module)
            {
                if($module["module"] == "Tables\\")
                {
                    continue;
                }
                $files = File::allFiles(base_path($module["path"]));
                $files = array_values(array_filter($files, function($item)
                {
                    return $item->getExtension() == "php";
                }));
                foreach($files as $file)
                {
                    $name = str_replace('\\\\','\\',$module["module"].str_replace("/",'\\',substr($file->getPath(), strlen(base_path($module["path"]))+1))."\\".pathinfo($file->getFileName(),\PATHINFO_FILENAME));
                    try{
                        $class = new ReflectionClass($name);
                    }catch(\Exception $e)
                    {
                        continue;
                    }
                    $classAnnotations = $annotationReader->getClassAnnotations($class);
                    if(!empty($classAnnotations))
                    {
                       // dd($classAnnotations);
                    }
                    $methods = $class->getMethods();
                    Logger::warn($class->name);
                    foreach($methods as $method)
                    {
                        if($method->class != $name)
                        {
                            continue;
                        }
                        try{

                         $reflectedMethod = new \ReflectionMethod($class->name, $method->name);
                         Logger::info($class->name."::".$method->name);
                        }catch(\Exception $e)
                        {
                            dd(["class"=>$class, "method"=>$method]);
                        }
                        $methodAnnotations = $annotationReader->getMethodAnnotations($reflectedMethod);
                        if(!empty($methodAnnotations))
                        {
                            //dd($methodAnnotations);
                        }
                    }
                }
            }
    }
    protected function generatePHPAPI()
    {
           $routes = Route::getRoutes()->getRoutes();//sortBy('uri');
        usort($routes, function($a, $b)
        {
            return $a->uri <=> $b->uri;
        });


        $path = join_paths(config('doc.php.path'), "source");
        $doc = new MarkdownWriter();
        $doc->data("title", "API usage");
        $doc->data("language_tabs",["php","json"]);
        $doc->data("search",true);
        //$doc->data("language_tabs","php");
        $done = [];
        foreach($routes as $route)
        {
             if(!is_string($route->action["uses"]))
            {
                Logger::warn('ignore '.$route->uri );
                continue;   
            }
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
            ->where('value','not like','{"exception":%')
            ->whereNotNull('value')
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
                if(isset($params["_id"]))
                {
                    unset($params["_id"]);
                }
                if(isset($params["_instance"]))
                {
                    unset($params["_instance"]);
                }
                if(isset($params["_timestamp"]))
                {
                    unset($params["_timestamp"]);
                }
                if(isset($params["_"]))
                {
                    unset($params["_"]);
                }
                if(isset($params["callback"]))
                {
                    unset($params["callback"]);
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
                        if(strpos($item, ":")!==false)
                            list($class, $param) = explode(":", $item);
                        else{
                            $class = $item;
                            $param = NULL;
                        }
                        $middleware = new stdClass();
                        $middleware->class = $class;
                        if(isset($param))
                        {
                            $instance = Api::unserialize($param); 
                            $middleware->instance = $instance;
                        }
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
                    $param->type = "";

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
                        return $item->instance->requirements??"";
                    },$params),
                     "type" => array_map(function($item)
                    {
                        return $item->instance->type??"";
                    },$params),
                    "default" => array_map(function($item)
                    {
                        return $item->instance->default??"";
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
            if(isset($docData->return) && mb_strlen($docData->return))
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
            chdir(config('doc.php.path'));
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
