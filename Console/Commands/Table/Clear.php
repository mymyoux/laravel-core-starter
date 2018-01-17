<?php

namespace Core\Console\Commands\Table;
use DB;
use Illuminate\Console\Command;
use Core\Util\ClassWriter;
use Schema;
use App;
class Clear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'table:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Table cache';

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
        $folder = base_path('bootstrap/tables');

        if(file_exists($folder))
            $this->recursiveRemoveDirectory($folder);
        if(!file_exists($folder))
        {
            mkdir($folder, 0777);
        }

    }
    function recursiveRemoveDirectory($directory)
    {
        foreach(glob("{$directory}/*") as $file)
        {
            if(is_dir($file)) { 
                $this->recursiveRemoveDirectory($file);
            } else {
                unlink($file);
            }
        }
        rmdir($directory);
        $this->info('tables cleared');
    }
}
