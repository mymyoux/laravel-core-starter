<?php

namespace Core\Http\Middleware\Api;

use Closure;
use DB;
use Route;
use Auth;
use Core\Exception\ApiException;
use Core\Listeners\CacheListener;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Stats as StatsService;
use Core\Model\Query;
use Api;
class Stats
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(config('api.stats')!==True)
        {
            $user = Auth::getUser();
            if(!isset($user) || !$user->isAdmin())
            {
                $response = $next($request);
                $this->logQueries();
                return $response;
            }
        }
        StatsService::addApiCall(Route::getFacadeRoot()->current());
        $response = $next($request);
        if(isset($response->exception) && !($response instanceof JsonResponse))
        {
            return $response;
        }
        $data = $response;
        if($response instanceof JsonResponse)
        {
            $data = $response->getOriginalContent();
        }
        $time = microtime(true)-LARAVEL_START;
        $time = floor($time*1000);

        $route = (array) Route::getFacadeRoot()->current();
        $route = ["uri"=>$route["uri"], "action"=>$route["action"]];
        if(!empty($route["action"]["middleware"]))
        {
            if(is_array($route["action"]["middleware"]))
            {
                foreach($route["action"]["middleware"] as $key=>$value)
                {
                    if(strpos($value, ":")!==False)
                    {
                        $middleware = explode(":", $value);
                        $route["action"]["middleware"][$key] = [$middleware[0]=>json_decode(base64_decode($middleware[1]), True)];
                    }
                }
            }
        }
        $data["stats"] = 
        [
            "route"=> $route,
            "time"      =>  $time,
            "queries"   =>  DB::getQueryLog(),
            "cache"   =>  CacheListener::getQueryLog()
        ];
        $api = StatsService::getApiStats();
        $data["stats"]["api"] = $api;
        $this->logQueries();
        return $data;
    }
    public function logQueries()
    {
         if(!Api::isMainCall())
         {
            return;
         }
        $queries = DB::getQueryLog();
        $min_time = config("log.database.min_time", 0);
        foreach($queries as $query)
        {
            if($query["time"] > $min_time)
            {
                Query::record($query);
            }
        }
    }
    
}
