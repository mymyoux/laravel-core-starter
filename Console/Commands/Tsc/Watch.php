<?php

namespace Core\Console\Commands\Tsc;
use Illuminate\Console\Command as BaseCommand;
use Core\Util\Command;
class Watch extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tsc:watch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile & watch tsc files';

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
      Command::executeRaw('tsc --watch');
    }
}
