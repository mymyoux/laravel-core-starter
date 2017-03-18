<?php

namespace Core\Console\Commands\Db;
use Db;
use Core\Console\Commands\CoreCommand;
use Tables\STATS_API_CALL;
use Tables\ERROR;
use Tables\STATS_WATCH;
use Tables\BEANSTALKD_LOG;
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
        STATS_API_CALL::where("created_time","<",Db::raw("NOW() - INTERVAL $months MONTH"))->delete();
        BEANSTALKD_LOG::where("created_time","<",Db::raw("NOW() - INTERVAL $months MONTH"))->delete();
        ERROR::where("created_time","<",Db::raw("NOW() - INTERVAL $months MONTH"))->delete();
        STATS_WATCH::where("created_time","<",Db::raw("NOW() - INTERVAL $months MONTH"))->delete();
    }
   
}
