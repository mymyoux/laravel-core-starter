<?php

namespace Core\Http\Controllers\Auth;

use Socialite;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Core\Connector\Manager;
use Core\Model\ConnectorUser;
use Auth;
use DB;
use App\User;
use Core\Models\Social;
use Core\Connector\Connector;
use Core\Http\Controllers\Controller;
use Core\Model\UserLoginToken;
use URL;
use Core\Events\SocialAddedEvent;
use Core\Events\SocialScopeChangedEvent;
use Core\Model\Connector\Manual;
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
        $api_token = $request->input('api_token');
        if(isset($api_token))
        {
            $request->session()->put('api_token',$api_token);
        }
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
            $connector = Connector::where(['name'=>$api])->first();
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
        $connector = ConnectorUser::leftJoin('connector','connector.connector_id','=','user_connector.connector_id')->where('user_id', '=', Auth::id())->where('connector.name', '=', $api)->first();
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
        return redirect($this->getDefaultURLRedirect());
    }
    public function getDefaultURLRedirect()
    {
        return "/";
    }
    /**
     * Obtain the user information from GitHub.
     *
     * @return Response
     */
    public function handleProviderCallback(Request $request, Response $response, $api)
    {
        if($api == 'manual')
        {
            $user       =  std($request->all());
        }else
        {
            $driver = Socialite::driver($api);
            $user       =  $driver->user();
        }
        $manager = new Manager();
        $connector  = $manager->get($api, $user);
        $connector->setScopes($request->session()->pull('scopes'));
        //$request->session()->forget('scopes');
        $class      = '\Core\Model\Connector\\' . ucfirst($api);
        $url_redirect = $this->getDefaultURLRedirect();
        if($request->session()->has('hash'))
        {
            $url_redirect = URL::route('/', ["#".$request->session()->get('hash')]);
            $request->session()->forget('hash');
        }
        if($request->session()->has('api_token'))
        {
            $token_user = User::findByApiToken($request->session()->get('api_token'));
            if(isset($token_user))
            {
                Auth::login($token_user);
            }
            $request->session()->forget('api_token');
        }
        // echo '<pre>';
        // var_dump($connector);
        // var_dump($user);
        if($api == 'manual')
        {
            $exist = $class::where('email', $user->email)->first();
            if(!Auth::check())
            {
                if(!password_verify($data->password, $exists->password))
                {
                    //bad password
                    return redirect($url_redirect);
                }
            }
        }else
        {
            $exist = $class::where('id', $user->id)->first();
        }
        if ($exist && Auth::check())
        {
            if ($exist->user_id != Auth::id())
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
                $exist = ConnectorUser::where(['email'=>$email])->first();
                if(!isset($exist))
                    $exist = User::where(['email'=>$email])->first();
            }


            if (is_null($exist))
            {
                $auth_user = $this->createUser($connector);

                $this->register($auth_user, $api);
            }
            else
            {
                
                $auth_user = User::find($exist->user_id);
            }

            $this->success($request, $auth_user);
        }
        $exist = ConnectorUser::where('user_id', '=', Auth::id())->where('connector_id', '=', $connector->getConnector()->getKey())->first();
        if (null === $exist)
        {
            
            $exist = ConnectorUser::firstOrCreate([
                'user_id'       => Auth::id(),
                'connector_id'  => $connector->getConnector()->getKey(),
                'access_token'  => $connector->getAccessToken(),
                'refresh_token' => $connector->getRefreshToken(),
                'expires_in'    => $connector->getExpiresIn(),
                'scopes'        => $connector->getScopes()!=NULL?implode(",", $connector->getScopes()):NULL,
                'email'         => $connector->getEmail()
            ]);
            event(new SocialAddedEvent($exist));
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
        $informations   = $class::where('user_id', '=', Auth::id())->first();
        $data           = $connector->toArray();
        if (null === $informations)
        {
            $data['user_id']    = Auth::id();
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
    public function loginManual(Request $request)
    {
        $data = std($request->all());
        
        $rawuser = Manual::where('email','=',$data->email)->first();

        if(!isset($rawuser))
        {
            return redirect('/', ['error'=>'no_email','error_type'=>'login']);
        }
        if(!password_verify($data->password, $rawuser->password))
        {
            //bad password
            return redirect('/', ['error'=>'bad_password','error_type'=>'login']);
        }
        $this->success($request, $rawuser->user);
        return redirect('/');

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
         //TABLE:Test token inserttestcontroller
         UserLoginToken::insert(["user_id"=>$auth_user->getKey(),"token"=>generate_token()]);
    }
    public function success(Request $request, $auth_user)
    {
         Auth::loginUsingId($auth_user->getKey());
    }
}
