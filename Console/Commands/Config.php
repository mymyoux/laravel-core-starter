<?php

namespace Core\Console\Commands;
use Db;
use Illuminate\Console\Command;
use Core\Util\ClassWriter;
use Core\Util\ClassWriter\Body\Table;
use Core\Util\ClassWriter\Body\General;
use Schema;
use File;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Illuminate\Support\Arr;
use Config as Conf;

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
class Config extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'config {key?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display config value';

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
       $key = $this->argument('key');
       if(isset($key))
       {
          return $this->displayCommand($key);
       }
       $configs = Conf::all();
        (new VarCloner)->cloneVar(Arr::dot($configs))->dump(new CliDumper);
    }
    protected function displayCommand($key)
    {
        if(!Conf::has($key))
        {
           return $this->error($key." doesn't exist");
        }
        $value = config($key);
         $this->dumpKey($key);
        if(is_numeric($value))
        {
            return $this->dumpScalar($value);
        }
        if(is_string($value))
        {
            return $this->dumpString($value);
        }
        if(is_bool($value))
        {
            return $this->dumpBoolean($value);
        }
   
        (new VarCloner)->cloneVar($value)->dump(new CliDumper);
    }
    protected function dumpScalar($value)
    {
        echo "\e[38;5;208m\e[1;38;5;38m$value\e[38;5;208m\e[m\n";
    }
    protected function dumpString($value)
    {
        echo "\e[38;5;208m\"\e[1;38;5;113m$value\e[38;5;208m\"\e[m\n";
    }
    protected function dumpKey($value)
    {
        echo "\e[38;5;208m\"\e[1;38;5;113m$value\e[38;5;208m\"\e[m \e[38;5;208m=\e[m ";
    }
    protected function dumpBoolean($value)
    {
        echo "\e[38;5;208m\e[1;38;5;208m".($value?"true":"false")."\e[38;5;208m\e[m\n";
    }
}
