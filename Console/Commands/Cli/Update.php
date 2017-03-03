<?php

namespace Core\Console\Commands\Cli;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use App;
use Illuminate\Foundation\Providers\ArtisanServiceProvider;
class Update extends Command
{
    protected $current_directory;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cli:update {--pull=d} {--cache=d} {--supervisor=d}';

    protected $defaultChoices = 
    [
        "pull"=>1,
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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        try
        {
            throw $e;
        }catch(\Exception $e)
        {
            //dd($e);
        }
    }
    protected function configure()
    {
          $this->current_directory = base_path();
          $choices = config("update.choices");
          foreach($choices as $key=>$value)
          {
            $this->defaultChoices[$key] = $value;
          }
    }
    /**
     * 
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
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
    protected function runCache()
    {
        //clear
         $this->call('redis:clear');
         $this->call('cache:clear');
         $this->call('config:clear');
         $this->call('route:clear');

         //build
         $this->call('config:cache');
         $this->call('route:cache');
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
        $result = $this->cmd("supervisorctl", ["restart",$env.":*"]);
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
}
