<?php

namespace Core\Queue\Console;

use Carbon\Carbon;
use Core\Queue\Worker;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Console\WorkCommand as BaseWorkCommand;
class WorkCommand extends BaseWorkCommand
{
    

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        //env queeue
        putenv("ENV_QUEUE=1");
        return parent::fire();
    }
    /**
     * Store a failed job event.
     *
     * @param  JobFailed  $event
     * @return void
     */
    protected function logFailedJob(JobFailed $event)
    {
        //don't log
        return;
    }

    /**
     * Get the queue name for the worker.
     *
     * @param  string  $connection
     * @return string
     */
    protected function getQueue($connection)
    {
        $queue = parent::getQueue($connection);
        if(config('queue.prefix'))
        {
            $queue = config('queue.prefix').$queue;
        }
        return $queue;
    }
}
