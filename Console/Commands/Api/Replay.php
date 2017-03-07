<?php

namespace Core\Console\Commands\Api;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use App;
use Illuminate\Foundation\Providers\ArtisanServiceProvider;
use Db;
use Core\Model\Api as ModelApi;
use Core\Exception\Exception;
use Api;
use Symfony\Component\Console\Terminal;

/**
 * Normal => should always be an user instance on the app
 */
use App\User;
class Replay extends Command
{
    protected $current_directory;
    protected $cache;
    protected $cachefilename;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:replay {id_api_call}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replay api call';

    /**
     * 
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $id_api_call = $this->argument('id_api_call');
        $call = ModelApi::find($id_api_call);
        if(!isset($call))
        {
            throw new Exception('no call with id '.$id_api_call);
        }
        if(isset($call->id_user))
        {
            $user = User::getById($call->id_user);
            if(!isset($user))
            {
               throw new Exception('no user with id '.$call->id_user);
            }
        }
        if(isset($call->id_user_impersonated))
        {
            $impersonated = User::getById($call->id_user_impersonated);
            if(!isset($impersonated))
            {
               throw new Exception('no user with id '.$call->id_user_impersonated);
            }
            $impersonated->setImpersonate($user);
            $user = $impersonated;
        }
        $result = Api::user($user)->get($call->path)->params(json_decode($call->params))->response();

        $keys = array_keys(array_filter(get_object_vars($result), function($item){return isset($item);}));
        $keys[] = "exit";
        while(True)
        {
            $width = (new Terminal())->getWidth();
            $white = str_repeat(" ", $width);
            $key = $this->choice('What do you want to see?', $keys, count($keys)-1);
            switch($key)
            {
                case "exit":
                break 2;
                case "exception":

                    $this->error($this->prepareObject($result->$key));
                break;
                default:
                    $this->info('<bg=white>'.$white.'</>');
                    $this->info($this->prepareObject($result->$key));
                    $this->info('<bg=white>'.$white.'</>');
             //   var_dump($result->$key);
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
