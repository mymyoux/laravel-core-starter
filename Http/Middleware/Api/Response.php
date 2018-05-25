<?php

namespace Core\Http\Middleware\Api;

use Closure;
use DB;
use Route;
use Auth;
use Core\Exception\ApiException;
use Core\Listeners\CacheListener;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\JsonResponse;
use Stats as StatsService;
use Core\Model\Query;
use Api;
use Logger;

class Response
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

        if ($response instanceof \Illuminate\Http\RedirectResponse || $response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse)
        {
            return $response;
        }
        
        if(isset($response->exception) && !($response instanceof JsonResponse))
        {
            return $response;
        }
        $data = $response;
        if($response instanceof JsonResponse)
        {
            $data = $response->getOriginalContent();
        }
        
        $this->addData( $data );
        

        return $data;
    }

    public function addData( &$data )
    {
        if (!isset($data['api_data']))
        {
            $data['api_data'] = [
                'id_user' => null
            ];
        }

        $data['api_data']['id_user'] = Auth::check() ? Auth::user()->getKey() : null;
    }
}
