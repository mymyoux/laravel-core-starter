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
use Logger;
class Stats
{
    private function getMemoryUsage( $raw = false )
    {
        $unit       = ['b','kb','mb','gb','tb','pb'];

        if (null !== $this->memory_usage)
            $data   = memory_get_peak_usage(true) - $this->memory_usage;
        else
            $data   = 0;

        if (true === $raw) return $data;

        if (0 === $data) return 0;

        return @round($data/pow(1024,($i=floor(log($data,1024)))),2).' '.$unit[$i];
    }

    private function getCpuUsage( $raw = false )
    {
        if (null !== $this->cpu_usage)
            $data   = sys_getloadavg()[0] - $this->cpu_usage;
        else
            $data   = 0;

        if (true === $raw) return $data;

        return $data;
    }
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
            if(!isset($user) || !$user->isRealAdmin())
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

        $this->memory_usage   = LARAVEL_RAM;
        $this->cpu_usage      = LARAVEL_CPU;

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
            "log"=> Logger::getOutput(),
            "route"=> $route,
            "time"      =>  $time,
            "queries"   =>  DB::getQueryLog(),
            "cache"   =>  CacheListener::getQueryLog(),
            'ram'   => $this->getMemoryUsage(),
            'cpu'   => $this->getCpuUsage()
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
        $min_time = config("log.database.min_time", 1000);
        foreach($queries as $query)
        {
            if($query["time"] > $min_time)
            {
                Query::record($query);
            }
        }
    }
    
}
