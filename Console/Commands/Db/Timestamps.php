<?php

namespace Core\Console\Commands\Db;
use DB;
use Core\Console\Commands\CoreCommand;
use Tables\Model\Stats\Api\Call;
use Tables\Model\Error;
use Tables\Model\Stats\Watch;
use Tables\Model\Beanstalkd\Log;
use App\Model\Ats\AtsApiCall;
use Logger;
class Timestamps extends CoreCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:timestamps {--suffix=at} {--precision=3}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Format all timestamps columns';

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
        $suffix = $this->option('suffix');
        $precision = (int)$this->option('precision');

        $database = 'time';
        $result = collect(Db::select('SELECT INFORMATION_SCHEMA.COLUMNS.* FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA="'.$database.'" ORDER BY INFORMATION_SCHEMA.COLUMNS.TABLE_NAME ASC, INFORMATION_SCHEMA.COLUMNS.ORDINAL_POSITION ASC'));
        $result = $result->filter(function($item)
        {
            return in_array($item->COLUMN_NAME, ['created_time','created_at','updated_time','updated_at']);
        })->values();
        
        $created_at = $result->filter(function($item)
        {
            return in_array($item->COLUMN_NAME, ['created_time','created_at']);
        })->values();
        $updated_at = $result->filter(function($item)
        {
            return in_array($item->COLUMN_NAME, ['updated_time','updated_at']);
        })->values();

        $created_at->each(function($item) use($suffix, $precision)
        {
            $column = "created_".$suffix;
            $request = "ALTER TABLE `".$item->TABLE_NAME."` CHANGE COLUMN ".$item->COLUMN_NAME." ".$column." TIMESTAMP(".$precision.") DEFAULT CURRENT_TIMESTAMP(".$precision.")";
            DB::statement($request);
        });

        $updated_at->each(function($item) use($suffix, $precision)
        {
            $column = "updated_".$suffix;
            $request = "ALTER TABLE `".$item->TABLE_NAME."` CHANGE COLUMN ".$item->COLUMN_NAME." ".$column." TIMESTAMP(".$precision.") DEFAULT CURRENT_TIMESTAMP(".$precision.") ON UPDATE CURRENT_TIMESTAMP(".$precision.")";
            DB::statement($request);
        });
    }
   
}
