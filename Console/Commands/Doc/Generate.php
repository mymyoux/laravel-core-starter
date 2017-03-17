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



            //$doc->title($route->uri, 2/*count($parts)*/);      
            $doc->code('php', "<?\nApi::path('".$route->uri."')->send()");                      
            $doc->code('php', "<?\n".preg_replace("/^    /m", "", ClassHelper::getMethodBody($route->action["uses"], True)));                      
            $exemple = STATS_API_CALL::where('path','=',$route->uri)
            ->where('value','not like','{"data":null%')
            ->orderBy('created_time','desc')->first();
            if(isset($exemple))
            {
                $json = $exemple->value;
                $json = json_decode($json);
                $json = $json->data;
                $doc->code('json', $json);  
            }
            $doc->aside($route->action["uses"]);     
            if(count($route->action["middleware"])>1)                 
            $doc->table(["middlewares"=>array_map(function($item)
                {
                    return explode(":", $item)[0];
                },array_values(array_filter($route->action["middleware"], function($item){return $item!="api";})))]);                      
        }
        $path = join_paths($path, "index.html.md");
        $old = file_get_contents($path);
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
}
