<?php

namespace Core\Console\Commands\Cli;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use App;
use Illuminate\Foundation\Providers\ArtisanServiceProvider;
use DB;
use Core\Model\Error;
use File;
use Logger;
use Illuminate\Console\Application;

class Update extends Command
{
    protected $current_directory;
    protected $cache;
    protected $cachefilename;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cli:update {--pull=d} {--composer=d} {--cache=d} {--sass=d} {--tsc=d} {--template=d} {--supervisor=d} {--migrate=d} {--cron=d} {--doc=d} {--execute-only}';

    protected $defaultChoices =
    [
        "pull"              => 1,
        "composer"          => 1,
        "migrate"           => 1,
        "sass"              => 0,
        "tsc"               => 0,
        "template"          => 0,
        "cache"             => 1,
        "supervisor"        => 1,
        'cron'              => 0,
        'doc'               => 1
    ];
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update project';

    /**
     *
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Error::mute();
        $this->current_directory = base_path();

        //chdir(base_path());
        $env = config('app.env', NULL);
        //verifications
        if(!isset($env))
        {
            $this->warn("APP_ENV is not set - remove cache you can try to restart the command");
            $this->call('cli:clear-cache');
            throw new \Exception('you must set APP_ENV to your .env file');
        }
        if($this->option('execute-only', False))
        {
            return $this->handleOption();
        }
        $this->info("Environment:\t".$env);
        if(config('update.user'))
        {
            $this->info("user:".config('update.user'));
        }
        if(config('update.group'))
        {
            $this->info("group:".config('update.group'));
        }


        $folder_permissions = [
            storage_path() =>0644,
            public_path() =>0644,
            base_path('bootstrap/cache') => 0644,
            base_path('bootstrap/tables') => 0644
        ];
        foreach($folder_permissions as $folder=>$right)
        {
            if(!file_exists($folder))
            {
                mkdir($folder, $right, True);
            }
            $min = file_right($folder, True);
            if($min < $right)
            {
                $this->warn($folder.' rights updated - found: '.$min);
                if(!file_exists($folder))
                {
                    mkdir($folder, $right);
                }
                if(config('update.user'))
                {
                    $this->chownRecursive($folder, config("update.user"));
                }
                if(config('update.group'))
                {
                    $this->chgrpRecursive($folder, config("update.group"));
                }
                $this->chmodRecursive($folder, "0".$right);
            }else
            {
                if(config('update.user'))
                {
                    $name = $this->getUserRecursive($folder);//posix_getpwuid(fileowner($folder))["name"];
                    if($name != config('update.user'))
                    {
                        $this->warn($folder.' owner updated - found: '.$name);
                        $this->chownRecursive($folder, config("update.user"));
                        if(config('update.group'))
                        {
                            $this->chgrpRecursive($folder, config("update.group"));
                        }
                    }
                }

            }
        }



        if( $this->option('verbose'))
            $this->info(json_encode(config('database'),\JSON_PRETTY_PRINT));
         //configure
         
          $choices = config("update.choices");
          foreach($choices as $key=>$value)
          {
            $this->defaultChoices[$key] = $value;
          }
          


        //execute choices
        $keys = array_keys($this->defaultChoices);
        $choices = [];
        foreach($keys as $key)
        {
            $choices[$key] = $this->option($key);
            if($choices[$key]=="d")
            {
                $choices[$key] = $this->defaultChoices[$key];
            }
        }
        $choices = array_reduce(array_keys($choices), function($previous, $key) use($choices)
        {
            if($choices[$key])
            {
                $previous[] = $key;
            }
            return $previous;
        }, []);

        $this->start($choices);

        $this->loadCacheFile();
        $this->cache["last_execution"] = date("Y-m-d H:i:s");
        $this->writeCache();


        $insert = array_reduce($choices, function($previous, $item)
        {
            $previous[$item] = 1;
            return $previous;
        }, []);
        $insert["project"] = config('update.project');
        Db::table(config('update.table'))->insert($insert);
    }
    protected function loadCacheFile()
    {
        $this->cachefilename = base_path(config("update.cache", "bootstrap/cache/update.php"));
          if(file_exists($this->cachefilename))
          {
            $this->cache = require $this->cachefilename;
          }else
            $this->cache = [];
    }
    protected function handleOption()
    {
        $keys = array_keys($this->defaultChoices);
        foreach($keys as $key)
        {
            if($this->option($key, False) == "1")
            {
                return call_user_func([$this, "run".ucfirst($key)]);
            }
        }
    }
    protected function start($choices)
    {
        $steps = count($choices)+1;
        $bar = $this->output->createProgressBar($steps);
        $bar->setFormatDefinition('custom', '<fg=cyan>%bar% %current%/%max%</><fg=yellow> -- %message%</>');
        $bar->setFormat('custom');
        foreach($choices as $choice)
        {
            $bar->setMessage('Running '.$choice."\n");
            $bar->advance();
            $result = $this->cmd(Application::phpBinary(), [Application::artisanBinary(), "cli:update", "--".$choice."=1","--execute-only"]);
            if(!$result["success"])
            {
                if(!$this->confirm('<error>An error has occurred, do you want to continue anyway?</error>'))
                {
                    Logger::error('aborted');
                    exit(1);
                    break;
                }
            }
            //call_user_func([$this, "run".ucfirst($choice)]);
        }
        $bar->setMessage("Exiting\n");
        $bar->advance();
    }

    protected function runPull()
    {
        $config_path = base_path(".gitmodules");
        $folders = ["."];
        if(file_exists($config_path))
        {
            $content = file_get_contents($config_path);
            $count = preg_match_all("/path *= *([^\n ]+)/", $content, $matches);
             if($count)
            {
                foreach($matches[1] as $match)
                {
                    $match = str_replace('"','',$match);
                    $folders[] = $match;
                }
            }
        }else
            Logger::warn('no submodules to pull');

        $folders = array_map(function($item)
        {
            if($item == ".")
            {
                return base_path();
            }
            return base_path($item);
        }, $folders);
        foreach($folders as $folder)
        {
            $this->pullGit($folder);
        }
    }
    protected function runComposer()
    {

        $this->loadCacheFile();
        $composer = base_path("composer.lock");
        $update = False;
        if(!file_exists($composer))
        {
            $update = True;
        }
        if(!$update)
        {
            $md5 = md5_file($composer);
            if(!isset($this->cache["composer.lock"]))
            {
                $update = True;
            }else
            if($this->cache["composer.lock"] != $md5)
            {
                $update = True;
            }
        }
        if($update)
        {
            $this->line("updating composer");
            $result = $this->cmd("composer", ["install"]);
            if(!$result["success"])
            {
                throw new \Exception("Error during composer install");
            }
            $md5 = md5_file($composer);
            $this->cache["composer.lock"] = $md5;
            $this->writeCache();
        }else
        {
            $this->line("no need for update");
        }
    }
    protected function runSass()
    {
        $this->call('sass:compile');
        $this->call('sass:cache');
    }
    protected function runTsc()
    {
        $env = config('app.env');
        //$env = "prod";
        $tsconfig_files = File::glob(resource_path('assets/ts/').'tsconfig.'.$env.'.*.json');
        if(empty($tsconfig_files))
        {
            $tsconfig_files = File::glob(resource_path('assets/ts/').'tsconfig.json');
        }
        foreach($tsconfig_files as $tsconfig)
        {
            $this->call('tsc:compile', ["path"=>$tsconfig]);
        }
        $this->call('tsc:cache');
    }
    protected function runTemplate()
    {
        $this->call('template:cache');
    }
    protected function runMigrate()
    {
        $this->call('phinx:migrate');
    }
    protected function runCron()
    {
        $this->call('cli:generate-cron');
    }
    protected function runCache()
    {
        //clear
         $this->call('cli:clear-cache');
         // $this->call('redis:clear');
         // $this->call('cache:clear');
         // $this->call('config:clear');
         // $this->call('route:clear');

         //build
         $this->call('config:cache');
         $this->call('route:cache');
         $this->call('optimize');
    }
    protected function pullGit($directory = NULL)
    {
        if(!isset($directory))
        {
            $directory = $this->current_directory;
        }else
        {
            //$directory = join_paths($this->current_directory, $directory);
        }
        $this->line("git pull: ".$this->getRelativePath($directory));
        //chdir($directory);
        $result = $this->cmd("git", ["pull"], True, $directory);
        if(!$result["success"])
        {
            throw new \Exception("Error during git pull: ".$directory);
        }
        //chdir($this->current_directory);
    }

    public function runSupervisor()
    {
        $this->call('supervisor:config');
        $this->call('supervisor:restart');
    }
    protected function cmd($command, $params = NULL, $execute = True, $dir = NULL)
    {
        if(isset($params))
        {
            $command.= " ".implode(" ", $params);
        }
        $this->line("execute: ".$command);
        $command.=" 2>&1";
        $output = [];
        $returnValue = NULL;
        if($execute)
        {
            $descriptorspec = array(
               0 => array("pipe", "r"),   // stdin is a pipe that the child will read from
               1 => array("pipe", "w"),   // stdout is a pipe that the child will write to
               2 => array("pipe", "w")    // stderr is a pipe that the child will write to
            );

            $process = proc_open($command, $descriptorspec, $pipes, $dir);
            if (is_resource($process)) {
                while ($s = fgets($pipes[1])) {
                   echo $s;
                   $output[] = $s;
                }
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $returnValue = proc_close($process);
            }
        }
        return ["output"=>$output, "returnValue"=>$returnValue, "success"=>$returnValue==0];
    }
    protected function runDoc()
    {
        //generate doc
         $this->call('doc:generate');
    }
    protected function getRelativePath($path)
    {
        $relative = mb_substr($path, strlen($this->current_directory));
        if(strlen($relative) === 0)
        {
            return ".";
        }
        return $relative;
    }
    protected function writeCache()
    {
        $data = "<?php\nreturn ".var_export($this->cache, True).";";
        file_put_contents($this->cachefilename, $data);
    }
    protected function getUserRecursive($path)
    {
        $files = get_files($path, True);

        $name = NULL;
        foreach($files as $file)
        {
            $temp = posix_getpwuid(fileowner($file))["name"];
            if($name != $temp && isset($name))
            {
                $name = "various";
            }else
            {
                $name = $temp;;
            }
        }
        return $name;
    }
    protected function chmodRecursive($path, $value)
    {
        if(is_string($value))
            $value = intval($value, 8);

         $files = get_files($path, True);
        foreach($files as $file)
        {
            chmod((string)$file, $value);
        }
    }
    protected function chgrpRecursive($path, $value)
    {
         $files = get_files($path, True);
        foreach($files as $file)
        {
            chgrp((string)$file, $value);
        }
    }
    protected function chownRecursive($path, $value)
    {
        $files = get_files($path, True);
        foreach($files as $file)
        {
            try
            {

                chown((string)$file, $value);
            }catch(\Exception $e)
            {
                Logger::error($file. " can't be chown");
               
                Logger::info('sudo chown '.$value.' '.$file);
                exec('sudo chown '.$value.' '.$file);
            }
        }
    }
}
