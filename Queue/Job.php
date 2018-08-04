<?php

namespace Core\Queue;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkInterface;
use Queue;
use Notification;
use DB;
use Core\Model\Beanstalkd;
use App\User;
use Auth;
use Logger;
use  Illuminate\Queue\Jobs\JobName;
use Illuminate\Contracts\Bus\Dispatcher;
use App;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\ManuallyFailedException;
use Core\Queue\Jobs\FakeBeanstalkdJob;

use Exception;
use Throwable;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Illuminate\Contracts\Cache\Repository as CacheContract;
//use Illuminate\Support\Facades\Redis;
use Cache;
class Job
{
    const DEFAULT_TTR = 429496729;
    private $data;
    private $class;
    private $identifier = null;
    private $user_id = null;

    use DispatchesJobs;

    public function __construct( $class, $data = NULL)
    {
        $this->tube   = $this->buildTubeName($class);
        Logger::warn($this->tube);
        $this->class        = $class;
        $this->data         = $data;
        $user = Auth::getUser();
        if(isset($user))
        {
            $this->user_id = $user->getKey();
        }
    }
    public function getTube()
    {
        return $this->tube;
    }
    public function getUnprefixedTube()
    {
        $prefix = config('app.env').'_';
        if(config('queue.prefix'))
        {
            $prefix .= config('queue.prefix');
        }
        return substr($this->tube, strlen($prefix));
    }
    public function identifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }
    public function user($user)
    {
        if(is_numeric($user))
        {
            $this->user_id = $user;
            return $this;
        }
        $this->user_id = $user->getKey();
        return $this;
    }
    public function data($data)
    {
        $this->data = $data;
        return $this;
    }
    public function set($name, $value)
    {
        if(!isset($this->_data))
        {
            $this->_data[] = [];
        }
        $this->_data[$name] = $value;
        return $this;
    }
    /**
     * Build tube name
     * @param  string $class Tube's name
     * @return string Tube's name  with prefix
     */
    protected function buildTubeName($class)
    {
        if (defined("$class::name"))
        {
            $tube = $class::name;
            $prefix = config('app.env').'_';
            if(config('queue.prefix'))
            {
                $prefix.= config('queue.prefix');
            }
            $tube = $prefix.$tube;

            return $tube;
        }

        $tube   = null;

        $index = strpos($class, 'Queue\\');
        if($index !== False)
        {
            $index+=6;
        }
        $index2 = strpos($class, 'Jobs\\');
        if($index2 !== False)
        {
            $index2 += 5;
        }
        if($index === False || ($index2 !== False && $index>$index2))
        {
            $index = $index2;
        }
        if($index === False)
        {
            throw new \Exception('Queue must be inside Queue or Jobs folder');
        }

        $path = substr($class, $index);
        $paths = explode('\\', strtolower($path));
        $last = array_pop($paths);
        if(!isset($tube))
        {
            $tube = $last;
        }
        if(!empty($paths))
        {
            $prefix = join("/", $paths);
            $tube = $prefix."/".$tube;
        }
        //prefix
        $prefix = config('app.env').'_';
        if(config('queue.prefix'))
        {
            $prefix.= config('queue.prefix');
        }
        $tube = $prefix.$tube;
        return $tube;
    }
    public function cancelAllPrevious()
    {
        $pheanstalk = Queue::getPheanstalk();
        $request = \Core\Model\Beanstalkd::where('queue', '=', $this->tube)
            ->whereIn("state", [Beanstalkd::STATE_CREATED, Beanstalkd::STATE_RETRYING, Beanstalkd::STATE_PENDING, Beanstalkd::STATE_FAILED_PENDING_RETRY ]);

        if (isset($this->user_id))
            $request->where('user_id', '=', $this->user_id);

        if(isset($this->identifier))
            $request->where('identifier', '=', $this->identifier);

        $previous = $request->get();

        if (!empty($previous))
        {
            foreach($previous as $log)
            {
                if(isset($log["id_beanstalkd"]))
                {
                    try
                    {
                        $job = $pheanstalk->peek( $log["id_beanstalkd"] );

                        $pheanstalk->delete($job);
                    }
                    catch(\Exception $e)
                    {
                        Logger::error('Error delete previous' . $e->getMessage());
                    }

                    $log->state = Beanstalkd::STATE_CANCELLED;
                    $log->save();
                }
            }
        }

        return $this;
    }

    public function throttle( $delay = PheanstalkInterface::DEFAULT_DELAY, $priority = PheanstalkInterface::DEFAULT_PRIORITY, $now = false )
    {
        return $this->cancelAllPrevious()->send($delay, $priority, $now);
    }

    private function sendAlert($now = false)
    {
        $request = \Core\Model\Beanstalkd::where('queue', '=', $this->tube)
            ->where('state', '=', Beanstalkd::STATE_EXECUTED_FRONT)
            ->where('created_at', '>=', DB::raw('NOW() - INTERVAL 1 HOUR'))
            ;

        $count = $request->count();

        if (0 === $count && $now === false)
        {
            // error recursive alert beanstlakd
            // Notification::alert('beanstalkd');
        }
    }

    public function sendNow()
    {
        return $this->send(PheanstalkInterface::DEFAULT_DELAY, PheanstalkInterface::DEFAULT_PRIORITY, true);
    }

    public function sendWeak($delay = PheanstalkInterface::DEFAULT_DELAY, $priority = PheanstalkInterface::DEFAULT_PRIORITY, $now = false)
    {
        $id = generate_token();
        $beanstalkd = std([
            'json'          => json_encode($this->data),
            'queue'         => $this->tube,
            'delay'         => $delay,
            'user_id'       => $this->user_id,
            'priority'      => $priority,
            'identifier'    => $this->identifier,
            'state'         => ($delay <= 0 ? Beanstalkd::STATE_CREATED : Beanstalkd::STATE_PENDING),
            'cls'           => $this->class,
            'queue_type'    =>"redis",
            'tries'=>0
        ]);

        try
        {
            $class  = $this->class;

            $job = new $class();
            $job->id = $id;
            $job->queue_type = "redis";
            $job->queue = $this->tube;
            $job->delay = $beanstalkd->delay;
            if (true === $now)
            {
                throw new \Pheanstalk\Exception\ConnectionException("NOW", 1);
            }
            $id_beanstalkd  = $this->dispatch( $job );
            Logger::info('dispatch');
        }
        catch (\Pheanstalk\Exception\ConnectionException $e)
        {
            if ($delay != PheanstalkInterface::DEFAULT_DELAY && App::runningInConsole())
            {
                Logger::warn('waiting for ' . $delay . ' secs...');
                sleep( $delay );
            }
            // launch inline if beanstalkd down
            $start_time = microtime(True);

            //force to be iso with queue
            $job = unserialize(serialize($job));
            // for sendWeak Now
            $job->loadDbData($beanstalkd);            

            if (false === $now && !($job instanceof \Core\Jobs\Slack))
            {
                //check if slack infinite loop otherwise
                $this->sendAlert($now);
            }
            $previous_user = Auth::user();

            $fakejob = new FakeBeanstalkdJob($job);
            $fakejob->setIsExecutedNow($now);
            $has_error = True;
            while($has_error && !$fakejob->hasFailed())
            {
                $has_error = True;
                try {
                    // First we will raise the before job event and determine if the job has already ran
                    // over the its maximum attempt limit, which could primarily happen if the job is
                    // continually timing out and not actually throwing any exceptions from itself.
                    $fakejob->tries();
                    if($fakejob->attempts()>1 && $now === true)
                    {
                        $delay = $fakejob->getDelayRetry();
                        if($delay)
                        {
                            sleep($delay);
                        }
                    }

                    $this->raiseBeforeJobEvent($fakejob);

                    $this->markJobAsFailedIfAlreadyExceedsMaxAttempts(
                        $fakejob
                    );
                    // Here we will fire off the job and let it process. We will catch any exceptions so
                    // they can be reported to the developers logs, etc. Once the job is finished the
                    // proper events will be fired to let any listeners know this job has finished.
                    app(Dispatcher::class)->dispatchNow($job);

                    $this->raiseAfterJobEvent($fakejob);
                    $has_error = False;
                } catch (Exception $e) {
                    if(Auth::isRealAdmin())
                        Logger::error($e->getMessage()."\n".$e->getFile().":".$e->getLine());
                    $this->handleJobException($fakejob, $e);
                } catch (Throwable $e) {
                    if(Auth::isRealAdmin())
                        Logger::error($e->getMessage()."\n".$e->getFile().":".$e->getLine());
                    $this->handleJobException(
                       $fakejob, new FatalThrowableError($e)
                    );
                }
            }


            if(isset($previous_user))
                Auth::setUser($previous_user);

            return true;
        }

        $beanstalkd->id_beanstalkd = $id_beanstalkd;

        Cache::forever($id, json_encode($beanstalkd));
        return $id_beanstalkd;
    }
    public function send($delay = PheanstalkInterface::DEFAULT_DELAY, $priority = PheanstalkInterface::DEFAULT_PRIORITY, $now = false)
    {
        $beanstalkd = \Core\Model\Beanstalkd::create([
            'json'          => json_encode($this->data),
            'queue'         => $this->tube,
            'delay'         => $delay,
            'user_id'       => $this->user_id,
            'priority'      => $priority,
            'identifier'    => $this->identifier,
            'state'         => ($delay <= 0 ? Beanstalkd::STATE_CREATED : Beanstalkd::STATE_PENDING),
            'cls' => $this->class
        ]);

        $id = $beanstalkd->id;

        try
        {
            $class  = $this->class;

            $job = new $class();
            $job->id = $id;
            $job->queue = $this->tube;
            $job->delay = $beanstalkd->delay;
            if (true === $now)
            {
                throw new \Pheanstalk\Exception\ConnectionException("NOW", 1);
            }
            $id_beanstalkd  = $this->dispatch( $job );
            Logger::info('dispatch');
        }
        catch (\Pheanstalk\Exception\ConnectionException $e)
        {
            if ($delay != PheanstalkInterface::DEFAULT_DELAY && App::runningInConsole())
            {
                Logger::warn('waiting for ' . $delay . ' secs...');
                sleep( $delay );
            }
            // launch inline if beanstalkd down
            $start_time = microtime(True);

            //force to be iso with queue
            $job = unserialize(serialize($job));
            if (false === $now && !($job instanceof \Core\Jobs\Slack))
            {
                //check if slack infinite loop otherwise
                $this->sendAlert($now);
            }
            $previous_user = Auth::user();

            $fakejob = new FakeBeanstalkdJob($job);
            $fakejob->setIsExecutedNow($now);
            $has_error = True;
            while($has_error && !$fakejob->hasFailed())
            {
                $has_error = True;
                try {
                    // First we will raise the before job event and determine if the job has already ran
                    // over the its maximum attempt limit, which could primarily happen if the job is
                    // continually timing out and not actually throwing any exceptions from itself.
                    $fakejob->tries();
                    if($fakejob->attempts()>1 && $now === true)
                    {
                        $delay = $fakejob->getDelayRetry();
                        if($delay)
                        {
                            sleep($delay);
                        }
                    }

                    $this->raiseBeforeJobEvent($fakejob);

                    $this->markJobAsFailedIfAlreadyExceedsMaxAttempts(
                        $fakejob
                    );
                    // Here we will fire off the job and let it process. We will catch any exceptions so
                    // they can be reported to the developers logs, etc. Once the job is finished the
                    // proper events will be fired to let any listeners know this job has finished.
                    app(Dispatcher::class)->dispatchNow($job);

                    $this->raiseAfterJobEvent($fakejob);
                    $has_error = False;
                } catch (Exception $e) {
                    if(Auth::isRealAdmin())
                        Logger::error($e->getMessage()."\n".$e->getFile().":".$e->getLine());
                    $this->handleJobException($fakejob, $e);
                } catch (Throwable $e) {
                    if(Auth::isRealAdmin())
                        Logger::error($e->getMessage()."\n".$e->getFile().":".$e->getLine());
                    $this->handleJobException(
                       $fakejob, new FatalThrowableError($e)
                    );
                }
            }


            if(isset($previous_user))
                Auth::setUser($previous_user);

            return true;
        }

        $beanstalkd->id_beanstalkd = $id_beanstalkd;
        $beanstalkd->save();

        return $id_beanstalkd;
    }
    protected function raiseBeforeJobEvent($job)
    {
        event(new JobProcessing(
            "beanstalkd", $job
        ));
    }
    protected function raiseAfterJobEvent($job)
    {
        event(new JobProcessed(
            "beanstalkd", $job
        ));
    }
    protected function raiseExceptionOccurredJobEvent($job, $e)
    {
        event(new JobExceptionOccurred(
            "beanstalkd", $job, $e
        ));
    }
    protected function markJobAsFailedIfAlreadyExceedsMaxAttempts($job)
    {

        $maxTries = $job->maxTries();

        if ($maxTries === 0 || $job->attempts() <= $maxTries) {
            return;
        }
        $job->markAsFailed();
        new JobFailed(
                "beanstalkd", $job, $e = new MaxAttemptsExceededException(
            'A queued job has been attempted too many times. The job may have previously timed out.'
        ) );

        throw $e;
    }
    protected function markJobAsFailedIfWillExceedMaxAttempts($job, $e)
    {
        $maxTries = $job->maxTries();

        if ($maxTries > 0 && $job->attempts() >= $maxTries) {
            $job->markAsFailed();
             new JobFailed(
                    "beanstalkd", $job, $e);
        }
    }
     protected function handleJobException($job, $e)
    {
        try {
            // First, we will go ahead and mark the job as failed if it will exceed the maximum
            // attempts it is allowed to run the next time we process it. If so we will just
            // go ahead and mark it as failed now so we do not have to release this again.
            $this->markJobAsFailedIfWillExceedMaxAttempts(
                 $job, $e
            );

            $this->raiseExceptionOccurredJobEvent(
                $job, $e
            );
        } finally {
            // // If we catch an exception, we will attempt to release the job back onto the queue
            // // so it is not lost entirely. This'll let the job be retried at a later time by
            // // another listener (or this same one). We will re-throw this exception after.
            // if (! $job->isDeleted()) {
            //     $job->release($options->delay);
            // }
        }
    }


}
