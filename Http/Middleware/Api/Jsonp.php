<?php

namespace Core\Http\Middleware\Api;

use Closure;
use Response;
use Request;
class Jsonp
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

   
        $input = $request->all();
        if(isset($input["__type"]) && $input["__type"] == "json")
        {
            foreach($input as $key=>$value)
            {
                $input[$key] = json_decode($value, True);
            }
            $request->replace($input);
            Request::replace($input);
        }

     //   header("Access-Control-Allow-Origin: *");
        //$response = $next($request);
        //$response->header('Access-Control-Allow-Origin', '*');
        $response = $next($request);
        
        if ($response instanceof \Illuminate\Http\RedirectResponse)
        {
            return $response;
        }

        return static::convert($request, $response);
    }
    public static function convert($request, $response)
    {
        $callback = $request->input('callback');
        if(isset($callback))
        {
            $response = Response::json($response)->setCallback($callback);
            //if(!$response = $response->header('Access-Control-Allow-Origin'))
        }else
        {
            $response = Response::json($response);
        }
        $response = $response->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, DELETE, OPTIONS');
        $response = $response->header('Access-Control-Allow-Origin', config("app.origin")??'*');
        $response = $response->header('Access-Control-Allow-Credentials', 'true');
        return $response;
    }
}
