<?php

namespace Core\Console\Commands\Sass;
use Illuminate\Console\Command as BaseCommand;
use Core\Util\Command;
use App;
use Logger;
class Cache extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sass:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache sass files';

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
         $path = resource_path('assets/sass/gulpfile.js');
        if(!file_exists($path))
        {
            Logger::warn('no gulpfile.js present - no sass cache');
            return;
        }
        $cachepath = storage_path('framework/cache/assets.php');
        $cache = [];
        if(file_exists($cachepath))
        {
            $cache = include $cachepath;
        }

        $cssfiles = rglob(public_path('css/').'*.css');
        $cssfiles = array_map(function($item)
        {
            return substr($item, strlen(public_path('css/')));
        },array_values(array_filter($cssfiles, function($item)
        {
            if(ends_with($item, '.min.css'))
            {
                return False;
            }
            if(ends_with($item, '.map.css'))
            {
                return False;
            }
            if(starts_with($item, public_path('css/map/')))
            {
                return False;
            }
            if(starts_with($item, public_path('css/min/')))
            {
                return False;
            }
            if(strpos($item, "/node_modules")!==False)
            {
                return False;
            }
            return True;
        })));

        chdir(dirname($path));
        $date = new \DateTime();
        Command::executeRaw('gulp', ['cache']);
        Command::executeRaw('gulp', ['cachemap']);
        foreach($cssfiles as $cssfile)
        {
            try
            {
                $min = join_paths('min/',$cssfile);
                $min = join_paths(dirname($min), basename($cssfile,".css").".min.css");
                $map = join_paths('map/',$cssfile);
                $map = join_paths(dirname($map), basename($cssfile,".css").".map.css");

                //use md5 of minfile
                $realpath = join_paths(public_path('css/'),$min);
                $count = 0;
                if(isset($cache[$cssfile]))
                {
                    $count = $cache[$cssfile]["count"];
                    $md5 = md5(file_get_contents($realpath));
                    if($md5 == $cache[$cssfile]["md5"])
                    {
                        Logger::warn("ignoring ".$cssfile);
                        continue;
                    }
                }
                $md5 = md5(file_get_contents($realpath));
                Logger::info("caching ".$cssfile);
                
                $cache[$cssfile] =
                [   
                    "md5"=>$md5,
                    "count"=>$count+1,
                    "suffix"=>$date->format('Y/m/d-H:i:s')."-".($count+1),
                    "min"=>$min,
                    "map"=>$map
                ] ;
                file_put_contents($cachepath, "<?php\nreturn ".var_export($cache, True).";");
            }catch(\Exception $e)
            {
                Logger::warn($e->getMessage());
            }
        }
        file_put_contents($cachepath, "<?php\nreturn ".var_export($cache, True).";");
    }
}
