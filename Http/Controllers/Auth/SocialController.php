<?php

namespace Core\Http\Controllers\Auth;

use Socialite;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Core\Connector\Manager;
use Core\Model\UserConnector;
use Auth;
use DB;
use App\User;
use Core\Models\Social;
use Core\Http\Controllers\Controller;
use Tables\USER_LOGIN_TOKEN;
use URL;
use Core\Events\SocialAddededEvent;
use Core\Events\SocialScopeChangedEvent;
class SocialController extends Controller
{
    //user:email,public_repo,repo,read:org
    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return Response
     */
    public function redirectToProvider(Request $request, $api)
    {
        $redirect = $request->input('hash');
        if(isset($redirect))
        {
             $request->session()->put('hash', $redirect);
        }
        $driver = Socialite::driver($api);
        //given scopes
        if($request->input('scopes'))
        {
            $scopes = array_map('trim', explode(',', $request->input('scopes')));
        }else
        {
            //default scopes
            $connector = Db::table('connector')->where(['name'=>$api])->first();
            if(isset($connector->scopes))
            {
                $scopes = array_map('trim', explode(',', $connector->scopes));
            }
        }
        if(!empty($scopes))
        {
            $driver = $driver->scopes($scopes);
        }else
        {
            $scopes = NULL;
        }
        $request->session()->put('scopes', $scopes);
        return $driver->redirect();
    }
    public function revokeScopes(Request $request, $api)
    {
        $redirect = $request->input('hash');
        if(isset($redirect))
        {
            $redirect = URL::route('/', ["#".$redirect]);
        }else
        {
            $redirect = "/";
        }
        $scopes = $request->input('scopes');
        if(!isset($scopes))
        {
            return redirect($redirect);
        }
        $scopes = explode(",", $scopes);
        $connector = UserConnector::leftJoin('connector','connector.id_connector','=','user_connector.id_connector')->where('id_user', '=', Auth::id())->where('connector.name', '=', $api)->first();
        if(!$connector)
        {
            return redirect($redirect);    
        }
        $current_scopes = isset($connector->scopes)?explode(',',$connector->scopes):[];
        
        $newscopes = array_diff($current_scopes, $scopes);
        if(empty($newscopes))
        {
            $connector->scopes = '';
        }else
        {
            $connector->scopes  = implode(",", $newscopes);
        }
        $connector->save();
        if($newscopes != $scopes)
        {
            event(new SocialScopeChangedEvent($connector, [], array_diff($current_scopes, $newscopes)));
        }

        return redirect($redirect);
    }
    public function logout(Request $request)
    {
        Auth::logout();
        return redirect('/');
    }
    /**
     * Obtain the user information from GitHub.
     *
     * @return Response
     */
    public function handleProviderCallback(Request $request, Response $response, $api)
    {
        
        $user       = Socialite::driver($api)->user();
        
        $manager = new Manager();

        $connector  = $manager->get($api, $user);
        $connector->setScopes($request->session()->pull('scopes'));
        //$request->session()->forget('scopes');
        $class      = '\Core\Model\Connector\\' . ucfirst($api);

        $url_redirect = "/";
        if($request->session()->has('hash'))
        {
            $url_redirect = URL::route('/', ["#".$request->session()->get('hash')]);
            $request->session()->forget('hash');
        }
        // echo '<pre>';
        // var_dump($connector);
        // var_dump($user);

        $exist = $class::where('id', $user->id)->first();
        if ($exist && Auth::check())
        {
            if ($exist->id_user != Auth::id())
            {
                // cant
                return redirect($url_redirect);
            }
        }

        if ($connector->getConnector()->isPrimary() && !Auth::check())
        {
            $email = $connector->getEmail();
            if(isset($email) && !isset($exist))
            {
                $exist = UserConnector::where(['email'=>$email])->first();
            }


            if (is_null($exist))
            {
                $auth_user = $this->createUser($connector);

                $this->register($auth_user, $api);
            }
            else
            {
                
                $auth_user = User::find($exist->id_user);
            }

            $this->success($request, $auth_user);
        }
        $exist = UserConnector::where('id_user', '=', Auth::id())->where('id_connector', '=', $connector->getConnector()->id_connector)->first();
        if (null === $exist)
        {
            
            $exist = UserConnector::firstOrCreate([
                'id_user'       => Auth::id(),
                'id_connector'  => $connector->getConnector()->id_connector,
                'access_token'  => $connector->getAccessToken(),
                'refresh_token' => $connector->getRefreshToken(),
                'expires_in'    => $connector->getExpiresIn(),
                'scopes'        => $connector->getScopes()!=NULL?implode(",", $connector->getScopes()):NULL,
                'email'=> $connector->getEmail()
            ]);
            event(new SocialAddededEvent($exist));
        }
        else
        {
            
            $last_scope = $exist->getScopes();
            $exist->access_token    = $connector->getAccessToken();
            $exist->refresh_token   = $connector->getRefreshToken();
            $exist->expires_in      = $connector->getExpiresIn();
            $exist->setScopes(array_merge($last_scope, $connector->getScopes()!=NULL? $connector->getScopes():[]));
            $exist->email          = $connector->getEmail();
            $exist->save();
            $new_scopes = $exist->getScopes();
            if($last_scope != $new_scopes)
                event(new SocialScopeChangedEvent($exist, array_diff($new_scopes, $last_scope), array_diff($last_scope, $new_scopes)));
        }

        $informations   = $class::where('id_user', '=', Auth::id())->first();
        $data           = $connector->toArray();

        if (null === $informations)
        {
            $data['id_user']    = Auth::id();
            // var_dump($class);
            $class::create( $data );
        }
        else
        {
            foreach ($data as $key => $value)
            {
                $informations->{ $key } = $value;
            }

            $informations->save();
        }
        
        return redirect($url_redirect);
    }
    protected function createUser($connector)
    {
        $auth_user = new User();
        $auth_user->login        = $connector->getUsername();
        $auth_user->num_connection  = 0;
        $auth_user->picture          = $connector->getAvatar();
        $auth_user->type = "user";
        //$auth_user->country         = $connector->getCountry();
        //$auth_user->full_name       = $connector->getFullName();
        $auth_user->email           = $connector->getEmail();
        $auth_user->temp = False;
        $auth_user->num_connection = 1;
        $auth_user->last_connection = time();

        //$auth_user->checkUserName();
        return $auth_user;
    }
    public function register($auth_user, $api)
    {
         $auth_user->save();
         USER_LOGIN_TOKEN::insert(["id_user"=>$auth_user->id_user,"token"=>generate_token()]);
    }
    public function success(Request $request, $auth_user)
    {
         Auth::loginUsingId($auth_user->id_user);
    }
}
