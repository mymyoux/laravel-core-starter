<?php

namespace Core\Http\Middleware\Api;

use Closure;
use Core\Exception\ApiException;
use Illuminate\Http\Response;
use Auth;
use User;
class Impersonate
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
        $id_impersonate = $request->input("id_impersonate");
        if(isset($id_impersonate))
        {
            $user = Auth::getUser();
            $impersonated = $user::find($id_impersonate);
            if(isset($impersonated) && $this->isAllowed($user, $impersonated))
            {
                $impersonated->setRealUser($user);
                Auth::setUser($impersonated);
            }
        }
        return $next($request);
    }
    protected function isAllowed($user, $impersonate)
    {
        return isset($user) && $user->getRealUser()->getKey() != $impersonate->getKey() && $user->getRealUser()->isAdmin() && !$impersonate->isAdmin();
    }
}
