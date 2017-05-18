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
use Event;
use Logger;
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
    // protected function logFailedJob(JobFailed $event)
    // {
    //     //don't log
    //     return;
    // }
    protected function listenForEvents()
    {
        Event::listen('Illuminate\Queue\Events\JobProcessing', static::class."@jobProcessing");
        return parent::listenForEvents();
    }
    public function jobProcessing($event)
    {
        $job = $event->job;
        Logger::info('<info>Processing:</info> '.$job->resolveName().":".$job->getJobId());
    }
     /**
     * Write the status output for the queue worker.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  bool  $failed
     * @return void
     */
    protected function writeOutput(Job $job, $failed)
    {
        if ($failed) {
            $this->output->writeln('<error>['.Carbon::now()->format('Y-m-d H:i:s').'] Failed:</error> '.$job->resolveName().":".$job->getJobId());
        } else {
            $this->output->writeln('<info>['.Carbon::now()->format('Y-m-d H:i:s').'] Processed:</info> '.$job->resolveName().":".$job->getJobId());
        }
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
         $prefix = config('app.env').'_';
        if(config('queue.prefix'))
        {
            $prefix .= config('queue.prefix');
        }
        $queue = $prefix.$queue;
        return $queue;
    }
}
