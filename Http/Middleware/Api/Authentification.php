<?php

namespace Core\Http\Middleware\Api;

use App\User;
use Core\Exception\ApiException;
use Core\Model\User\Token\One;

use Closure;
use Auth;
use Api;

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
        $token  = $request->input('api_token');
        $rtoken = $request->input('rtoken');

        if(isset($rtoken))
        {
            $id_user   = One::getToken($rtoken);

            if(isset($id_user))
            {
                $user = User::find($id_user);
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
        
        if (!isset($token) && !Auth::check())
        {
            $session_token = User::getSessionToken();
            
            if ($session_token)
            {
                $token = $session_token;
            }
        }

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
                $session_token = User::getSessionToken();
                if (isset($session_token) && $token === $session_token)
                {
                    User::destroySessionToken();
                }
        
                throw new ApiException('bad_token');
            }
        }
        return $next($request);
    }
}
