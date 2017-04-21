<?php

namespace Core\Console\Commands\Sass;
use Illuminate\Console\Command as BaseCommand;
use Core\Util\Command;
use App;
class Compile extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sass:compile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile sass files';

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
        Command::executeRaw('gulp sass'.(App::isLocal()?'':':'.App::environment()));
    }
}
