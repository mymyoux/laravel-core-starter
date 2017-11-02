<?php

namespace Core\Console\Commands\Db;
use Db;
use Core\Console\Commands\CoreCommand;
use Tables\Model\Stats\Api\Call;
use Tables\Model\Error;
use Tables\Model\Stats\Watch;
use Tables\Model\Beanstalkd\Log;
class Clean extends CoreCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean db';

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
        $months = 2;
        Call::where("created_time","<",Db::raw("NOW() - INTERVAL $months MONTH"))->delete();
        Log::where("created_time","<",Db::raw("NOW() - INTERVAL $months MONTH"))->delete();
        Error::where("created_time","<",Db::raw("NOW() - INTERVAL $months MONTH"))->delete();
        Watch::where("created_time","<",Db::raw("NOW() - INTERVAL $months MONTH"))->delete();
    }
   
}
