<?php
namespace Core\Model;
use Core\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Tables\QUERY_LOG;
use Auth;
use Route;
use App;
class Query extends \Tables\Model\Query\Log
{
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $table = QUERY_LOG::TABLE;
	protected $primaryKey = 'id_query_log';

	protected static function boot()
    {
        parent::boot();
    }
    protected function record($query)
    {
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
