<?php

namespace Core\Console\Commands\Tsc;
use Illuminate\Console\Command as BaseCommand;
use Core\Util\Command;
use Logger;
use File;
class Cache extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tsc:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache tsc files';

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
        $path = resource_path('assets/ts/gulpfile.js');
        if(!file_exists($path))
        {
            Logger::warn('no gulpfile.js present - no tsc cache');
            return;
        }
        $cachepath = storage_path('framework/cache/assets.php');
        $cache = [];
        if(file_exists($cachepath))
        {
            $cache = include $cachepath;
        }

        $jsfiles = rglob(public_path('js/').'*.js');
        $jsfiles = array_map(function($item)
        {
            return substr($item, strlen(public_path('js/')));
        },array_values(array_filter($jsfiles, function($item)
        {
            if(ends_with($item, '.min.js'))
            {
                return False;
            }
            if(ends_with($item, '.map.js'))
            {
                return False;
            }
            if(starts_with($item, public_path('js/map/')))
            {
                return False;
            }
            if(starts_with($item, public_path('js/min/')))
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
        foreach($jsfiles as $jsfile)
        {
            try
            {
                $realpath = join_paths(public_path('js/'),$jsfile);
                $count = 0;
                if(isset($cache[$jsfile]))
                {
                    $count = $cache[$jsfile]["count"];
                    $md5 = md5(file_get_contents($realpath));
                    if($md5 == $cache[$jsfile]["md5"])
                    {
                      // Logger::warn("ignoring ".$jsfile);
                        continue;
                    }
                }
                $md5 = md5(file_get_contents($realpath));
                Logger::info("caching ".$jsfile);
                
                $min = join_paths('min/',$jsfile);
                $min = join_paths(dirname($min), basename($jsfile,".js").".min.js");
                $map = join_paths('map/',$jsfile);
                $map = join_paths(dirname($map), basename($jsfile,".js").".map.js");

                Command::executeRaw('gulp', ['cache','--file='.$jsfile]);
                Command::executeRaw('gulp', ['cachemap','--file='.$jsfile]);

                

                $cache[$jsfile] =
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
