<?php

namespace Core\Queue;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkInterface;
use Queue;
use Notification;
use DB;
use Core\Model\Beanstalkd;

class Job
{
    private $data = [];
    private $data_json = null;
    private $class = null;
    private $identifier = null;
    private $id_user = null;

    use DispatchesJobs;

    public function __construct( $class, $data, $id_user = null)
    {
        $this->tube         = $class;
        $this->class        = '\App\Jobs\\' . ucfirst($class);
        $this->data         = $data;
        $this->id_user      = $id_user;
        $this->data_json    = json_encode($data);
    }

    // public function isConnected()
    // {
    //     // if (!$this->beanstalkd)
    //     // {
    //     //     $config             = $this->sm->get('AppConfig')->get('beanstalkd');

    //     //     $this->ip           = $config['ip'];
    //     //     $this->port         = $config['port'];

    //     //     $this->beanstalkd   = new Pheanstalk($this->ip, $this->port);
    //     // }

    //     // return $this->beanstalkd->getConnection()->isServiceListening();
    // }

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
                        if (php_sapi_name() === 'cli')
                        echo $e->getMessage() . PHP_EOL;
                        //beanstalkd reloaded ?
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
            'json'          => $this->data_json,
            'queue'         => $this->tube,
            'delay'         => $delay,
            'id_user'       => $this->id_user,
            'priority'      => $priority,
            'identifier'    => $this->identifier,
            'state'         => ($delay <= 0 ? Beanstalkd::STATE_CREATED : Beanstalkd::STATE_PENDING)
        ]);

        $id = $beanstalkd->id;

        $this->job['_id_beanstalkd'] = $id;
        if(isset($id))
        {
            $data_json = ['_id_beanstalkd' => $id];
        }else
        {
            $data_json = $this->data;
        }

        try
        {
            $class          = $this->class;

            if (true === $now)
            {
                throw new \Pheanstalk\Exception\ConnectionException("NOW", 1);
            }

            $id_beanstalkd  = $this->dispatch(new $class( $data_json ));
        }
        catch (\Pheanstalk\Exception\ConnectionException $e)
        {
            if ($delay != PheanstalkInterface::DEFAULT_DELAY && php_sapi_name() === 'cli')
            {
                // $this->sm->get('Log')->warn('waiting for ' . $delay . ' secs...');
                sleep( $delay );
            }
            // launch inline if beanstalkd down
            $start_time = microtime(True);
            if (false === $now)
                $this->sendAlert($now);

            // $user = isset($this->id_user)?$this->sm->get('UserTable')->getUser($this->id_user):NULL;
            // $listener->setUser($user);

            $job = new $class( $this->data );
            $job->handle();

            $total_time = round((microtime(True) - $start_time)*1000);

            $beanstalkd->state      = $now ? Beanstalkd::STATE_EXECUTED_NOW : Beanstalkd::STATE_EXECUTED_FRONT;
            $beanstalkd->duration   = $total_time;
            $beanstalkd->tries      = 1;
            $beanstalkd->save();

            return true;
        }
        // $id_beanstalkd = $this->beanstalkd->useTube($this->getTube())->put(json_encode($data_json), $priority, $delay);
        // $this->sm->get('BeanstalkdLogTable')->setBeanstalkdID($id, $id_beanstalkd);
        $beanstalkd->id_beanstalkd = $id_beanstalkd;
        $beanstalkd->save();

        return $id_beanstalkd;
    }
}
