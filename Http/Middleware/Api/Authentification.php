<?php

namespace Core\Http\Middleware\Api;

use Closure;
use App\User;
use Auth;
use Core\Exception\ApiException;
class Authentification
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
        $token = $request->input('api_token');
        
        if(isset($token))
        {
            $user = User::findByApiToken($token);
            //TODO:get good instance of user 
            if(isset($user))
            {
                $token_impersonate = $request->input('api_token_impersonate');
                if(!$user->isAdmin() && isset($token_impersonate))
                {
                    $admin = User::findByApiToken($token_impersonate);
                    if(isset($admin) && $admin->isAdmin())
                    {
                        $user->setRealUser($admin);
                    }
                }
                Auth::setUser($user);

            }else
            {
                throw new ApiException('bad_token');
            }
        }
        return $next($request);
    }
}
