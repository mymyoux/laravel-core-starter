<?php

namespace Core\Http\Middleware\Api;

use Closure;
use Core\Exception\ApiException;
use Illuminate\Http\Response;
use Core\Model\Api;
use Auth;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model as BaseModel;

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
        // if (is_array($response))
        //     return ["data"=>$response];

        // if ($response instanceof Collection)
        //     return ["data"=>$response];
        
        // if ($response instanceof BaseModel)
        //     return ["data"=>$response];

        if (true === method_exists($response, 'getOriginalContent'))
            return ["data" => $response->getOriginalContent()];
        
        return ['data' => $response];
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
