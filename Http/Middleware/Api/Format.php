<?php

namespace Core\Http\Middleware\Api;

use Closure;
use Core\Exception\ApiException;
use Illuminate\Http\Response;
use Core\Model\Api;
use Auth;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Request;
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
        
        if ($response instanceof \Illuminate\Http\RedirectResponse)
        {
            return $response;
        }
        if($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse)
            return $response;
        if (true === method_exists($response, 'getOriginalContent'))
        {
            return ["data" => $response->getOriginalContent()];
        }
     
        
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
                    $insert = false;
                    $roles  = config('api.record.role');
                    foreach ($roles as $role)
                    {
                        if ($user->getRealUser()->hasRole( $role ))
                        {
                            $insert = true;
                            break;
                        }
                    }

                    if (true === $insert)
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
