<?php

namespace Core\Console\Commands\Cli;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use App;
use Illuminate\Foundation\Providers\ArtisanServiceProvider;
use Db;

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
    protected $signature = 'cli:update {--pull=d} {--composer=d} {--cache=d} {--supervisor=d} {--migrate=d}';

    protected $defaultChoices = 
    [
        "pull"=>1,
        "composer"=>1,
        "migrate"=>1,
        "cache"=>1,
        "supervisor"=>1,
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
        $env = env('APP_ENV', NULL);
        //verifications
        if(!isset($env))
        {
            throw new \Exception('you must set APP_ENV to your .env file');
        }
        if(!is_writable(storage_path()))
        {
            throw new \Exception(storage_path()." must be writable");
        }





        $this->info("Environment:\t".env('APP_ENV'));
        if( $this->option('verbose'))
            $this->info(json_encode(config('database'),\JSON_PRETTY_PRINT));
         //configure
         $this->current_directory = base_path();
          $choices = config("update.choices");
          foreach($choices as $key=>$value)
          {
            $this->defaultChoices[$key] = $value;
          }
          $this->cachefilename = base_path(config("update.cache", "bootstrap/cache/update.php"));
          if(file_exists($this->cachefilename))
          {
            $this->cache = require $this->cachefilename;
          }else
            $this->cache = [];


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
    protected function start($choices)
    {
        $steps = count($choices)+1;
        $bar = $this->output->createProgressBar($steps);
        $bar->setFormatDefinition('custom', '%bar% %current%/%max% -- %message%');
        $bar->setFormat('custom');
        foreach($choices as $choice)
        {
            $bar->setMessage('Running '.$choice."\n");
            $bar->advance();
            call_user_func([$this, "run".ucfirst($choice)]);
        }
        $bar->setMessage("Exiting\n");
        $bar->advance();
    }
     
    protected function runPull()
    {
        $folders = config("update.pull");
        if(empty($folders))
        {
            $folders = ["."];
        }
        foreach($folders as $folder)
        {
            $this->pullGit($folder);
        }
    }
    protected function runComposer()
    {
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
    protected function runMigrate()
    {
        $this->call('phinx:migrate');
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
            $directory = join_paths($this->current_directory, $directory);
        }
        $this->line("git pull: ".$this->getRelativePath($directory));
        chdir($directory);
        $result = $this->cmd("git", ["pull"]);
        if(!$result["success"])
        {
            throw new \Exception("Error during git pull: ".$directory);
        }
        chdir($this->current_directory);
    }

    public function runSupervisor()
    {
        $env = App::environment();
        $result = $this->cmd("supervisorctl", ["restart",config('update.supervisor.prefix', '').$env.":*"]);
    }
    protected function cmd($command, $params = NULL, $execute = True)
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

            $process = proc_open($command, $descriptorspec, $pipes);
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
}
