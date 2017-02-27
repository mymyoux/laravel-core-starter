<?php

namespace Core\Http\Middleware\Api;

use Closure;
use Core\Exception\ApiException;
use Illuminate\Http\Response;
use Api;
use Auth;
class Acl
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
         $acl = Api::unserialize($params);
         $user = Auth::getUser();
         if(!$acl->isAllowed($user))
         {
            throw new ApiException('you_are_not_allowed');
         }
         return $next($request);
    }
}
