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

class Job
{
    private $data = [];
    private $data_json = null;
    private $class = null;
    private $identifier = null;
    private $id_user = null;

    use DispatchesJobs;

    public function __construct( $class, $data)
    {
        $this->tube   = $this->buildTubeName($class);
        $this->class        = $class;
        $this->data         = $data;
        $user = Auth::getUser();
        if(isset($user))
        {
            $this->id_user = $user->id_user;
        }
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
            $this->id_user = $user;
            return $this;
        }
        $this->id_user = $user->id_user;
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
     * @param  [type] $class [description]
     * @return [type]        [description]
     */
    protected function buildTubeName($class)
    {
        $tube   = defined("$class::name")?$class::name:NULL;
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
        return $tube;
    }
    public function cancelAllPrevious()
    {
        $queue = 'slack';
        $pheanstalk = Queue::getPheanstalk();
        $request = \Core\Model\Beanstalkd::where('queue', '=', $this->tube)
            ->whereIn("state", [Beanstalkd::STATE_CREATED, Beanstalkd::STATE_RETRYING, Beanstalkd::STATE_PENDING ]);

        if (isset($this->id_user))
            $request->where('id_user', '=', $this->id_user);

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
            ->where('created_time', '>=', DB::raw('NOW() - INTERVAL 1 HOUR'))
            ;

        $count = $request->count();

        if (0 === $count && $now === false)
        {
            Notification::alert('beanstalkd');
        }
    }

    public function sendNow()
    {
        return $this->send(PheanstalkInterface::DEFAULT_DELAY, PheanstalkInterface::DEFAULT_PRIORITY, true);
    }

    public function send($delay = PheanstalkInterface::DEFAULT_DELAY, $priority = PheanstalkInterface::DEFAULT_PRIORITY, $now = false)
    {
        $beanstalkd = \Core\Model\Beanstalkd::create([
            'json'          => json_encode($this->data),
            'queue'         => $this->tube,
            'delay'         => $delay,
            'id_user'       => $this->id_user,
            'priority'      => $priority,
            'identifier'    => $this->identifier,
            'state'         => ($delay <= 0 ? Beanstalkd::STATE_CREATED : Beanstalkd::STATE_PENDING),
            'cls' => $this->class
        ]);

        $id = $beanstalkd->id;


       $user = isset($this->id_user) ? User::getById( $this->id_user ) : NULL;

        try
        {
            $class  = $this->class;

            if (true === $now)
            {
                throw new \Pheanstalk\Exception\ConnectionException("NOW", 1);
            }
            $job = new $class();
            $job->id = $id;
            $job->queue = $this->tube;
            $id_beanstalkd  = $this->dispatch( $job );
        }
        catch (\Pheanstalk\Exception\ConnectionException $e)
        {
            if ($delay != PheanstalkInterface::DEFAULT_DELAY && php_sapi_name() === 'cli')
            {
                Logger::warn('waiting for ' . $delay . ' secs...');
                sleep( $delay );
            }
            // launch inline if beanstalkd down
            $start_time = microtime(True);
            if (false === $now)
                $this->sendAlert($now);


            $job = new $class( $this->data );
            $job->handle();

            $total_time = round((microtime(True) - $start_time)*1000);

            $beanstalkd->state      = $now ? Beanstalkd::STATE_EXECUTED_NOW : Beanstalkd::STATE_EXECUTED_FRONT;
            $beanstalkd->duration   = $total_time;
            $beanstalkd->tries      = 1;
            $beanstalkd->save();

            return true;
        }

        $beanstalkd->id_beanstalkd = $id_beanstalkd;
        $beanstalkd->save();

        return $id_beanstalkd;
    }
}
