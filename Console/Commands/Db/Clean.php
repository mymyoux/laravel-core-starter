<?php

namespace Core\Console\Commands\Db;
use Db;
use Core\Console\Commands\CoreCommand;
use Tables\STATS_API_CALL;
use Tables\ERROR;
use Tables\STATS_WATCH;
use Tables\BEANSTALKD_LOG;
use Logger;
class Clean extends CoreCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clean {--all=0} {--day=60}';

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
        $all = $this->option('all');
        $day = (int)$this->option('day');
        if($all != "0" && $all!="false")
        {
            $all = True;
        }else {
            $all = False;
        }
        $months = 2;
        if($day<0)
        {
            $day = 0;
        }
        Logger::warn("These tables data will be erased until: $day days");
        Logger::info("\tstats_api_call");
        Logger::info("\terror");
        Logger::info("\tstats_watch");
        if($all)
        {
            Logger::info("\tbeanstalkd_log");
        }
        if (!$this->confirm('Do you want to continue?')) {
            Logger::info("abort");
            return;
        }
        Logger::info("deleting stats_api_call");
        STATS_API_CALL::where("created_time","<",Db::raw("NOW() - INTERVAL $day DAY"))->delete();
        if($all)
        {
            Logger::info("deleting beanstalkd_log");
            BEANSTALKD_LOG::where("created_time","<",Db::raw("NOW() - INTERVAL $day DAY"))->delete();
        }
        Logger::info("deleting error");
        ERROR::where("created_time","<",Db::raw("NOW() - INTERVAL $day DAY"))->delete();
        Logger::info("deleting stats_watch");
        STATS_WATCH::where("created_time","<",Db::raw("NOW() - INTERVAL $day DAY"))->delete();
        Logger::info("done");
    }
   
}
