<?php

namespace Core\Console\Commands\Phinx;
use DB;
use Illuminate\Console\Command;
use Core\Util\ClassWriter;
use Schema;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

use Phinx\Console\PhinxApplication;
use Phinx\Migration\Manager;
use Phinx\Config\Config;
use App;
use Request;

class Status extends PhinxCommand
{
    /**
     * 
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'phinx:status {--folder=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get migrations statuses';

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
        $manager = $this->getManager();
        $env = App::environment();
        $manager->printStatus($env);
        //echo $manager->getOutput()->fetch();
    }
}
