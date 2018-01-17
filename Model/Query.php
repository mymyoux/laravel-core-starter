<?php
namespace Core\Model;
use Core\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Auth;
use Route;
use App;
class Query extends \Tables\Model\Query\Log
{
	protected static function boot()
    {
        parent::boot();
    }
    protected function record($query)
    {
        if(!config('stats.sql'))
        {
            return;
        }
        $type = explode(" ", $query["query"])[0];
    	$rawsql = $this->getSql($query);
        
        $data = [
            "type" => $type,
            "query"=> $rawsql,
            "time"=>$query["time"],
            "is_front" => !App::runningInConsole(),
            "is_cron" => App::runningInCron(),
            "is_queue" => App::runningInQueue(),
            "stack"=>NULL
        ];
        $this->insert($data);
    }
    protected function getSql($model)
    {
        $replace = function ($sql, $bindings)
        {
            $needle = '?';
            foreach ($bindings as $replace){
                $pos = strpos($sql, $needle);
                if ($pos !== false) {
                    $sql = substr_replace($sql, $replace, $pos, strlen($needle));
                }
            }
            return $sql;
        };
        $sql = $replace($model["query"], $model["bindings"]);
        return $sql;
    }
}
