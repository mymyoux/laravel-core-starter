<?php

namespace Core\Http\Middleware\Api;

use Closure;
use Api;
use Request;
use Core\Exception\ApiException;
class Paginate
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
        $params = Api::unserialize($params);
        $paginate = $request->input('paginate');
        $paginate = $params->format($paginate);
        if(isset($paginate["keys"]))
        {
            foreach($paginate["keys"] as $key)
            {
                if(!$params->isAllowed($key))
                {
                    throw new ApiException('key_not_allowed:'.$key);
                }
            }
        }
        $input = Request::input();
        $input["paginate"] = $paginate;
        Request::replace($input);
        return $next($request);
    }
    
}
