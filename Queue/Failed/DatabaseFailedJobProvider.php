<?php

namespace Core\Queue\Failed;


use Illuminate\Queue\Failed\DatabaseFailedJobProvider as BaseDatabaseFailedJobProvider;
use Logger;
use Carbon\Carbon;
class DatabaseFailedJobProvider extends BaseDatabaseFailedJobProvider
{
	    /**
     * Log a failed job into storage.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $payload
     * @param  \Exception  $exception
     * @return int|null
     */
    public function log($connection, $queue, $payload, $exception)
    {
        $failed_at = Carbon::now();
        $rawpayload = json_decode($payload);
        $job = unserialize($rawpayload->data->command);
        $id_beanstalkd = $job->id;
        $exception = (string) $exception;
        return  $this->getTable()->insertGetId(compact(
            'connection', 'queue', 'payload', 'exception', 'failed_at','id_beanstalkd'
        ));
    }
}
