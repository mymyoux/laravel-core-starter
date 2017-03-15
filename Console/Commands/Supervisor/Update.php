<?php

namespace Core\Console\Commands\Supervisor;
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
class Update extends CoreCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supervisor:update {group?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update supervisor';

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
        $env = App::environment();
        if($env !== "prod")
        {
            $env .= "_";
        }else
        {
            $env = "";
        }
        $group = $this->argument('group')??config('queue.supervisor.default.group', '');
        Command::execute('supervisorctl',['update', $env.$group]);
    }
}
