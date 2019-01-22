<?php

namespace Core\Console\Commands\Cli;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use App;
use Illuminate\Foundation\Providers\ArtisanServiceProvider;
use DB;
use Core\Model\Error;
use File;
use Logger;
use Illuminate\Console\Application;

class Config extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cli:config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate config.json';

    /**
     *
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
       $config = config();
       $config = $config->all();

       file_put_contents(base_path(config('update.config')), json_encode($config, \JSON_PRETTY_PRINT));
    }
}
