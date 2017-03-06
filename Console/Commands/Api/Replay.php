<?php

namespace Core\Console\Commands\Api;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use App;
use Illuminate\Foundation\Providers\ArtisanServiceProvider;
use Db;

class Replay extends Command
{
    protected $current_directory;
    protected $cache;
    protected $cachefilename;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:replay {id_api_call}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replay api call';

    /**
     * 
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
         
    }
}
