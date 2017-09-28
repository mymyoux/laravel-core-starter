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
use Api as ApiService;
/**
 * Normal => should always be an user instance on the app
 */
use App\User;
use Logger;
class Call extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:call {--path=} {--id_user=?} {data?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Api call';

    /**
     * 
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = $this->option('path');
        $id_user = $this->option('id_user');
        $data = $this->argument('data');
        if(isset($data))
        {
            $data = json_decode(base64_decode($data), False);
        }else
        {
            $data = new \stdClass;
        }
        if(!isset($data->add_params)){
            $data->add_params = NULL;
        }
        if(!isset($data->params)){
            $data->params = NULL;
        }
        if(!isset($data->api_user)){
            $data->api_user = NULL;
        }
        if(!isset($data->api_user) && isset($id_user))
        {
            $data->api_user = $id_user;
        }
        $result = ApiService::path($path)->params($data->params)->user($data->api_user)->response($data->add_params);
        if(isset($result->stats) && isset($result->stats["log"]))
            $log = $result->stats["log"];
        
        if(isset($log))
        {
            $result->stats = ["log"=>$log];
        }else
        {
            unset($result->stats);
        }
        echo "------start-data-----\n";
        echo json_encode(
            $result)."\n";
        echo "------end-data-----\n";
    //    Logger::info($result);
    }
}
