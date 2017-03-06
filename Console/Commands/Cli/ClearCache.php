<?php

namespace Core\Console\Commands\Cli;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use App;
use Illuminate\Foundation\Providers\ArtisanServiceProvider;
use Db;

class ClearCache extends Command
{
    protected $current_directory;
    protected $cache;
    protected $cachefilename;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cli:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all project';

    /**
     * 
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
         $this->call('redis:clear');
         $this->call('cache:clear');
         $this->call('config:clear');
         $this->call('route:clear');
    }
}
