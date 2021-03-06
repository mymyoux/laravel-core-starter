<?php

namespace Core\Http\Middleware\Api;

use Closure;
use Response;

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
     //   header("Access-Control-Allow-Origin: *");
        //$response = $next($request);
        //$response->header('Access-Control-Allow-Origin', '*');
        $response = $next($request);
        
        if ($response instanceof \Illuminate\Http\RedirectResponse || $response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse)
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
