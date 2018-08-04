<?php

namespace Core\Http\Middleware\Api;

use Closure;
use Response;
use Request;
class Jsonp
{
    protected function decode($input)
    {
        foreach($input as $key=>$value)
        {
           
            
            if(is_string($value) && (starts_with($value, "[") || starts_with($value, "{") || starts_with($value, '"')) )
            {
                $tmp = json_decode($value, True);
                if($tmp !== False)
                {
                    $input[$key] = $tmp;
                }
            }
            if(is_array($input[$key]))
            {
                
                $input[$key] = $this->decode($input[$key]);
            }
        }
        return $input;
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

        $input = $request->all();

        $input = $this->decode($input);
        $request->replace($input);
        Request::replace($input);

        
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
