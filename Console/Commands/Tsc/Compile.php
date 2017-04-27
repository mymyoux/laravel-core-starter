<?php

namespace Core\Console\Commands\Tsc;
use Illuminate\Console\Command as BaseCommand;
use Core\Util\Command;
use Logger;
class Compile extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tsc:compile {path?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile tsc files';

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
      chdir(resource_path('assets/ts'));
      $path = $this->argument("path");
      if(isset($path))
      {
          Logger::info('execute '.$path);
        Command::executeRaw('tsc', ["-p", $path]);
      }else
      Logger::info('execute tsc');
        Command::executeRaw('tsc');
    }
}
