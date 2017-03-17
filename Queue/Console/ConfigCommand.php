<?php

namespace Core\Queue\Console;

use Illuminate\Queue\Listener;
use Core\Console\Commands\CoreCommand;
use Illuminate\Queue\ListenerOptions;
use Core\Util\ClassHelper;
use Core\Util\ModuleHelper;
use Core\Queue\JobHandler;
use Logger;
use File;
use App;
use Core\Queue\Job;
class ConfigCommand extends CoreCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update queue config file';
    protected $jobFolders = [];
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function start()
    {
        //get all modules
        $modules = ModuleHelper::getModulesFromComposer();
        $paths = array_column($modules, "path");

        //looking for all files under jobs folders
        foreach($paths as $path)
        {
            $this->searchJobsFolder($path);
        }
        if(empty($this->jobFolders))
        {
            Logger::warn('no jobs folder');
            return;
        }
        //transforms files into job instances
        $jobs = array_map(
            function($file){
                $class = ClassHelper::getFullClassName($file->getRealPath());
                return $class;

            },array_flatten(File::allfiles(array_flatten($this->jobFolders))));

        $jobs = array_filter($jobs, function($item)
        {
            return is_subclass_of($item, JobHandler::class);
        });

        $app = app();
        $jobs = array_map(function($item) use($app)
        {
            return $app->make($item);
        }, $jobs);

        //generate supervisor configuration
        usort($jobs, function($a, $b)
        {
            return get_class($a) <=> get_class($b);
        });
        $env_prefix = "";
        $env = App::environment();
        if($env !== "prod")
        {
            $env_prefix =$env."_";
        }
        $jobs = array_map(function($job) use($env_prefix)
        {
            $jobservice = new Job(get_class($job));
            $config = array_merge(

            config('queue.supervisor.default')??[],
            [
                "command"=>"php artisan queue:work --queue=".$jobservice->getUnprefixedTube(),
                "directory"=>base_path(),
                "name" => $env_prefix.$jobservice->getTube(),
            ], 
            $job->supervisor??[]
            );
            if(!isset($config["stdout_logfile"]) && config('queue.supervisor.logs'))
            {
                $config["stdout_logfile"] = join_paths(config('queue.supervisor.logs'),$jobservice->getUnprefixedTube()).'.log';
            }
            if(isset($config["numprocs"]) && $config["numprocs"])
            {
                $config["process_name"] = "%(program_name)s_%(process_num)02d";
            }
            return $config;
        }, $jobs);

        $groups = [];
        foreach($jobs as &$job)
        {
            if(!isset($groups[$job["group"]]))
            {
                $groups[$job["group"]] = [];
            }
            $groups[$job["group"]][] = $job["name"];
            unset($job["group"]);
        }

        $supervisor = "";
        //generate supervisor file
        foreach($jobs as &$job)
        {
            $supervisor.= "[program:".$job["name"]."]\n";
            unset($job["name"]);
            foreach($job as $key=>$value)
            {
                $supervisor.= $key."=".$value."\n";
            }
            $supervisor.="\n";
        }   
        foreach($groups as $name=>$group)
        {
            $supervisor.="\n[group:".$env_prefix.$name."]\n";
            $supervisor.="programs=".join(",", $group)."\n";
            $supervisor.="priority=999\n";
        }

        $supervisor_path = config('supervisor.configuration')??base_path('bootstrap/supervisor.conf');
        if(!file_exists($supervisor_path))
        {
            $old_supervisor = "";
        }else
        {
            $old_supervisor = file_get_contents($supervisor_path);
        }
        if($supervisor != $old_supervisor)
        {
            Logger::info("$supervisor_path updated");
            file_put_contents($supervisor_path, $supervisor);
            $this->call('supervisor:reread');
            $this->call('supervisor:update');
        }
    }
    public function searchJobsFolder($path)
    {
        $folders = File::directories($path);
        foreach($folders as $folder)
        {
            $parts = explode("/", $folder);
            if(array_last($parts) == "Jobs")
            {
                $this->pushJobFolder($folder);
            }else
            {
                $this->searchJobsFolder($folder);
            }
        }
    }
    protected function pushJobFolder($folder)
    {
        $this->jobFolders[] = $folder;
    }

    
}
