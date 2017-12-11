<?php

namespace Core\Http\Middleware\Api;

use Closure;
use Core\Exception\ApiException;
use Illuminate\Http\Response;
use Core\Model\Api;
use Auth;

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
        if (is_array($response))
            return ["data"=>$response];

        return ["data"=>$response->getOriginalContent()];
    }
    public function terminate($request, $response)
    {
        if (config('api.record.insert') !== false)
        {
            if (config('api.record.role') !== null)
            {
                $user = Auth::getUser();
                
                if(isset($user))
                {
                    if ($user->getRealUser()->hasRole( config('api.record.role') ))
                    {
                        Api::record(
                            $request,
                            $response
                        );
                    }
                }
            }
            else
            {
                Api::record(
                    $request,
                    $response
                );
            }
        }

        return $response;
    }
}
