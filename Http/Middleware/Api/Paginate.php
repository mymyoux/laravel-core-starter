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
class Paginate
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
        $response = $next($request);
        dd($paginate);
        
        return $response;
    }
    
}
