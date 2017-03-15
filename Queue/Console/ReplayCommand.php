<?php

namespace Core\Queue\Console;

use Core\Console\Commands\CoreCommand;
use Symfony\Component\Console\Input\InputOption;
use App;
use Illuminate\Foundation\Providers\ArtisanServiceProvider;
use Db;
use Core\Model\Api as ModelApi;
use Core\Exception\Exception;
use Api;
use Symfony\Component\Console\Terminal;

use Logger;
use Auth;
use Core\Model\Beanstalkd;

/**
 * Normal => should always be an user instance on the app
 */
use App\User;
class ReplayCommand extends CoreCommand
{
    protected $current_directory;
    protected $cache;
    protected $cachefilename;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:replay {id} {queue?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replay queue job';

    /**
     *
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        define('REPLAY', true);
        $id             = $this->argument('id');
        $queue_type     = $this->argument('queue', null);
        if(!isset($id))
        {
            Logger::error('No id given - you must pass it');
            exit();
        }
        $ids = explode(",", $id);
        $original_id = $id;


        $rebuild_ids = array();
        foreach($ids as $id)
        {
            if(($index = strpos($id, "-")) !== False)
            {
                $subids = explode("-", $id);
                if(count($subids) != 2)
                {
                    throw new \Exception("Bad format:".$original_id);
                }
                if(!is_numeric($subids[0]) || !is_numeric($subids[1]))
                {
                    throw new \Exception("Bad format:".$original_id);
                }
                $min = intval(trim($subids[0]));
                $max = intval(trim($subids[1]));
                if($min>$max)
                {
                    $tmp = $min;
                    $min = $max;
                    $max = $tmp;
                }
                for($i=$min; $i<=$max; $i++)
                {
                    $rebuild_ids[] = $i;
                }
            }
            elseif(($index = strpos($id, "+")) !== False)
            {
                $id = str_replace("+", "", $id);
                if(!is_numeric($id))
                {
                    throw new \Exception("Bad format:".$original_id);
                }
                $moreids = $this->getBeanstalkdLogTable()->getIdsGreaterThanOrEqual(intval(trim($id)), $queue_type);
                $rebuild_ids = array_merge($rebuild_ids, $moreids);
            }
            else
            {
                if(is_numeric($id))
                {
                    $rebuild_ids[] = intval(trim($id));
                }else
                {
                   throw new \Exception("Bad format:".$original_id);
                }
            }
        }
        $rebuild_ids = array_unique($rebuild_ids);

        $failed = [];
        foreach($rebuild_ids as $id)
        {
            try
            {
                $request = \Core\Model\Beanstalkd::where('id', '=', $id);

                if ($queue_type !== null)
                    $request->where('queue', '=', $queue_type);

                $result = $request->first();

                if(!isset($result))
                {
                    $failed[] = $id;
                    Logger::error('No record with id '.$id);
                    continue;
                }
                if(config('queue.prefix'))
                {
                    $result->queue = substr($result->queue, strlen(config('queue.prefix')));
                }
                if(!isset($result->queue))
                {
                    $queue = $this->params()->fromRoute('queue');
                    if(!isset($queue))
                    {
                        Logger::error('This record['.$id.'] has no queue registered you must pass it');
                        $failed[] = $id;
                        continue;
                    }
                    $result->queue = $queue;
                }

                $user = isset($result->id_user) ? User::getById( $result->id_user ) : NULL;

                $result->state      = Beanstalkd::STATE_REPLAYING;
                $result->tries++;
                $result->save();

                Logger::normal("replay job: ".$id);
                $class        = $result->cls;

                if(isset($result->id_user))
                {
                    Auth::loginUsingId($result->id_user);
                }else
                {
                    Auth::logout();
                }
                $job = new $class();
                $job->id = $result->id;
                $job->loadDbData($result);
                $job->handle();

                $result->state      = Beanstalkd::STATE_REPLAYING_EXECUTED;
                $result->save();
            }
            catch(\Exception $e)
            {
                $failed[] = $id;

                Logger::error($e->getMessage());

                $result->state      = Beanstalkd::STATE_REPLAYING_FAILED;
                $result->save();
            }
        }
        foreach($rebuild_ids as $id)
        {
            if(in_array($id, $failed))
            {
                Logger::error($id." failed");
            }else
            {
                Logger::info($id." success");
            }
        }
    }
    protected function  prepareObject($data)
    {
        if(is_array($data) || is_object($data))
        {
            return json_encode($data, \JSON_PRETTY_PRINT);
        }
        return $data;
    }
}
