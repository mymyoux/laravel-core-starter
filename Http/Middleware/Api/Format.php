<?php

namespace Core\Http\Middleware\Api;

use Closure;
use Core\Exception\ApiException;
use Illuminate\Http\Response;
use Core\Model\Api;
class Format
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
        
        if(isset($response->exception))
        {
            return $response;
        }
        return ["data"=>$response->getOriginalContent()];
    }
    public function terminate($request, $response)
    {
        Api::record(
            $request,
            $response
        );
        return $response;
    }
}
