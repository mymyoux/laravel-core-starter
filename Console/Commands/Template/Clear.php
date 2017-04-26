<?php

namespace Core\Console\Commands\Template;
use Db;
use Illuminate\Console\Command;
use Core\Util\ClassWriter;
use Schema;
use App;
use Api;
use App\User;
use Tables\TEMPLATE;
use Logger;
use File;
class Clear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'template:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear templates';

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
        TEMPLATE::delete();
        $success = File::deleteDirectory(storage_path('framework/cache/views/'), true);
    }
}
