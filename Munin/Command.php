<?php

namespace Core\Munin;

use Illuminate\Console\Command as BaseCommand;
use DB;

class Command extends BaseCommand
{
    protected $description = 'Munin stats';

    public function __construct()
    {
        $class = get_class($this);
        $class = str_replace('App\Console\Commands\\', '', $class);
        $class = str_replace('\\', ':', $class);
        $class = strtolower($class);
        $this->signature = $class . ' {action?}';

        parent::__construct();
    }

    public function echoConfig() 
    {
        throw new \Exception("override echoConfig", 500);
    }
    public function echoValue() 
    {
        throw new \Exception("override echoValue", 500);
    }

    public function handle()
    {
        $action = $this->argument('action');

        if (isset($action) && $action === 'config')
        {
            echo $this->echoConfig();
        }
        else
        {
            echo $this->echoValue();
        }
    }
}
