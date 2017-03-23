<?php

namespace Core\Console\Commands\Cli;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use App;
use Illuminate\Foundation\Providers\ArtisanServiceProvider;
use Db;
use Logger;

class Project extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cli:project';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Displays project information';

    /**
     * 
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
         Logger::info('Project name: '.config('app.name'));
         Logger::info('Project directory: '.getcwd());
    }
}
