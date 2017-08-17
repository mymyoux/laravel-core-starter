<?php

namespace Core\Console\Commands\Sass;
use Illuminate\Console\Command as BaseCommand;
use Core\Util\Command;
class Watch extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sass:watch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile && watch sass files';

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
        chdir(resource_path('assets/sass'));
        
        // $this->call('sass:compile');
        // Command::executeRaw("node node_modules/node-sass/bin/node-sass --output-style expanded --source-map-embed --watch . --output ../../../public/css");
        Command::executeRaw("gulp sass:watch");
    }
}
