<?php

namespace Core\Http\Middleware\Api;

use Closure;
use Response;
use Api;
use Logger;
use Illuminate\Http\JsonResponse;
class Data
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
     //   header("Access-Control-Allow-Origin: *");
        //$response = $next($request);
        //$response->header('Access-Control-Allow-Origin', '*');
        $response = $next($request);
        $data = Api::popApiData();
        if(!empty($data))
        {
            if($response instanceof JsonResponse)
            {
                $response->api_data = $data;
            }else
            $response["api_data"] = $data;
        }
        return $response;
    }
}
