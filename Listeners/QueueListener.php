<?php
namespace Core\Listeners;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobExceptionOccurred;

use Core\Model\Beanstalkd;
use Core\Queue\Jobs\FakeBeanstalkdJob;
use Db;
use App;
use Auth;
use Logger;
class QueueListener
{

    protected static $event;
    protected static  $job;
    protected static  $start;

	 /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }
    protected function getJob($event)
    {
        if(isset(static::$job) && $event->job === static::$event->job )
        {
            static::$event = $event;
            return static::$job;
        }
        Logger::warn('reset time');
        static::$start = microtime(True);
        static::$event = $event;
        $job = $event->job;
        if(method_exists($job, "getOriginalJob"))
        {
            //fake job for front
            $job = $job->getOriginalJob();
        }else
        {
            $payload = $job->payload();
            $rawjob = $payload["data"]["command"];
            $job = unserialize($rawjob);
        }
        return static::$job = $job;
    }
    protected function getDuration()
    {
        if(!isset(static::$start))
        {
            return -1;
        }
        $time = round((microtime(True) - static::$start)*1000);
        static::$start = NULL;
        return $time;
    }
    /**
     * Handle the event.
     *
     * @param  OrderShipped  $event
     * @return void
     */
    public function handle($event)
    {
    	if($event instanceof JobProcessing)
        {
            $this->handleProcessing($event);
        }
        if($event instanceof JobProcessed)
        {
            $this->handleProcessed($event);
        }
        if($event instanceof JobExceptionOccurred)
        {
            $this->handleFailed($event);
        }
        // if($event instanceof JobFailed)
        // {
        //     $this->handleFinalFailed($event);
        // }
    }
    public function handleProcessing($event)
    {
        $job = $this->getJob($event);
        if(isset($job->id_user))
        {
            Auth::loginUsingId($job->id_user);
        }else
        {
            Auth::logout();
        }
        Logger::info("processing:".$job->id_user." ".App::runningInQueue());
        Beanstalkd::where('id', '=', $job->id)
        ->update(["state"=>Beanstalkd::STATE_EXECUTING, "tries"=>Db::raw('tries + 1')]);
    }

    public function handleProcessed($event)
    {
        $job = $this->getJob($event);
        Logger::info("processed:".$job->id);

        $state = Beanstalkd::STATE_EXECUTED;
        if(!App::runningInConsole() && static::$event->job instanceof FakeBeanstalkdJob)
        {
            if(static::$event->job->isExecutedNow())
            {
                $state = Beanstalkd::STATE_EXECUTED_NOW;
            }else
            {
                $state = Beanstalkd::STATE_EXECUTED_FRONT;
            }
        }
        Beanstalkd::where('id', '=', $job->id)
        ->update(["state"=>$state, "duration"=>$this->getDuration()]);
    }
    public function handleFailed($event)
    {
        if($event->job->hasFailed())
        {
            return $this->handleFinalFailed($event);            
        }
        $job = $this->getJob($event);
        Logger::warn("failed:".$job->id);
        Beanstalkd::where('id', '=', $job->id)
        ->update(["state"=>Beanstalkd::STATE_FAILED_PENDING_RETRY, "duration"=>$this->getDuration()]);
    }
    public function handleFinalFailed($event)
    {
        $job = $this->getJob($event);
        Logger::error("final failed:".$job->id);
        Beanstalkd::where('id', '=', $job->id)
        ->update(["state"=>Beanstalkd::STATE_FAILED, "duration"=>$this->getDuration()]);
    }

}
