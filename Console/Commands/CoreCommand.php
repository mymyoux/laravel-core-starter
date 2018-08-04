<?php

namespace Core\Console\Commands;

use DB;
use Illuminate\Console\Command;
use Schema;
use ReflectionClass;
use File;

use Core\Model\Cron;
use Core\Model\Cron\Log as CronLog;

use Logger;

class CoreCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description 	= 'needs to be defined';
    public $crontab_config 	= null; // '* * * * *';
    public $crontab_options = null; // '* * * * *';

    private $memory_usage;
    private $cpu_usage;
    private $execution_time;

    private $cron_object;
    private $log_object;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        
    }

    public function handle()
    {
    	try
    	{
            $this->start();
            
            $this->cron_object->status = Cron::STATE_OK;
	    }
	    catch (\Exception $e)
	    {
	    	Logger::critical( $e->getMessage() );
            Logger::normal( $e->getTraceAsString() );
            
            $this->cron_object->status = Cron::STATE_KO;
        }
        
        $this->terminated();
    }

    public function getSignature()
    {
    	return $this->signature;
    }


	protected function storeUsage()
    {
        $this->memory_usage   = memory_get_peak_usage(true);
        $this->cpu_usage      = sys_getloadavg()[0];
        $this->execution_time = microtime(true);
    }

    protected function initCron()
    {
    	$name = $this->signature;
        $cron = Cron::where('name', '=', $name)->first();

        if (null === $cron)
        {
            $cron = Cron::create([
                'name'              => $name,
                'status'            => Cron::STATE_PROCESSING,
                'last_launch_date'  => DB::raw('NOW()'),
                'directory' 		=> base_path() . '/'
            ]);

            Logger::info('Cron `' . $name . '` inserted');
        }
        else
        {
        	$cron->status 			= Cron::STATE_PROCESSING;
        	$cron->last_launch_date = DB::raw('NOW()');
        	$cron->save();
        }

        $this->cron_object  = $cron;

        return $cron;
    }

    // public function updateLog( array $options, $save = false )
    // {
    //     foreach ($options as $key => $value)
    //     {
    //         $this->log_object->{ $key } =  $value;
    //     }

    //     if (true === $save)
    //     {
    //     	$this->cron_object->save();
    //     	$this->log_object->save();
    //     }
    // }

    private function getMemoryUsage( $raw = false )
    {
        $unit       = ['b','kb','mb','gb','tb','pb'];

        if (null !== $this->memory_usage)
            $data   = memory_get_peak_usage(true) - $this->memory_usage;
        else
            $data   = 0;

        if (true === $raw) return $data;

        if (0 === $data) return 0;

        return @round($data/pow(1024,($i=floor(log($data,1024)))),2).' '.$unit[$i];
    }

    private function getCpuUsage( $raw = false )
    {
        if (null !== $this->cpu_usage)
            $data   = sys_getloadavg()[0] - $this->cpu_usage;
        else
            $data   = 0;

        if (true === $raw) return $data;

        return $data;
    }

    private function getExecutionTime( $raw = false )
    {
        if (null !== $this->cpu_usage)
            $data   = microtime(true) - $this->execution_time;
        else
            $data   = 0;

        if (true === $raw) return round($data);

        return round($data) . ' sec';
    }

    public function terminated()
    {
        $this->cron_object->last_execution_time = $this->getExecutionTime(true);

        $this->cron_object->save();

        Logger::setDebug( true );

        Logger::info( '[RAM] ' . $this->getMemoryUsage() );
        Logger::info( '[LOAD] ' . $this->getCpuUsage() );
        Logger::info( '[TIME] ' . $this->getExecutionTime() );

        // $options = [
        //     'ram'               => $this->getMemoryUsage( true ),
        //     'load'              => $this->getCpuUsage( true ),
        //     'execution_time'    => $this->getExecutionTime( true ),
        //     'errors'            => Logger::getMetric('error'),
        //     'warnings'          => Logger::getMetric('warn'),
        //     'critical'          => Logger::getMetric('critical'),
        //     'insert'            => Logger::getMetric('insert'),
        //     'update'            => Logger::getMetric('update'),
        //     'delete'            => Logger::getMetric('delete'),
        //     'select'            => Logger::getMetric('select'),
        // ];

        // $status = 'ok';

        // if ($options['errors'] > 0 || $options['critical'] > 0)
        //     $status = 'ko';

        // // if ($options['critical'] > 0)
        // //     $options['critical_message'] = Logger::getCriticalMessage();

        // $options['status'] = $status;

        // $this->updateLog($options, true);
    }
}
