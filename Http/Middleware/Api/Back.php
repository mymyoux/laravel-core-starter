<?php

namespace Core\Http\Middleware\Api;

use Closure;
use Core\Exception\ApiException;
use Illuminate\Http\Response;
use Api;
use Stats;
use Route;
use Request;
class Back
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $params)
    {
        if(Api::isFrontCall())
        {
            throw new ApiException('route_not_authorized_for_front_call');
        }
        return $next($request);
    }
}
