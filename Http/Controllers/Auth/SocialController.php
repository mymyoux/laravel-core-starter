<?php

namespace Core\Http\Controllers\Auth;

use Socialite;
use Illuminate\Http\Request;
use Core\Connector\Manager;
use Core\Model\UserConnector;
use Auth;
use DB;
use App\User;
use Core\Models\Social;
use Core\Http\Controllers\Controller;
use Tables\USER_LOGIN_TOKEN;

class SocialController extends Controller
{
    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return Response
     */
    public function redirectToProvider(Request $request, $api)
    {
        Auth::logout();
        return Socialite::driver($api)->redirect();
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
    public function handleProviderCallback(Request $request, $api)
    {
        
        $user       = Socialite::driver($api)->user();
        
        $manager = new Manager();

        $connector  = $manager->get($api, $user);
        $class      = '\Core\Model\Connector\\' . ucfirst($api);

        // echo '<pre>';
        // var_dump($connector);
        // var_dump($user);

        $exist = $class::where('id', $user->id)->first();
        if ($exist && Auth::check())
        {
            if ($exist->id_user != Auth::user()->id)
            {
                // cant
                return redirect('/');
            }
        }

        if ($connector->getConnector()->isPrimary() && !Auth::check())
        {
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
                'scopes'        => $connector->getScopes(),
            ]);
        }
        else
        {
            
            $exist->access_token    = $connector->getAccessToken();
            $exist->refresh_token   = $connector->getRefreshToken();
            $exist->expires_in      = $connector->getExpiresIn();
            $exist->scopes          = $connector->getScopes();
            $exist->save();
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
         USER_LOGIN_TOKEN::insert(["id_user"=>$auth_user->id_user,"token"=>generate_token()]);
    }
    public function success(Request $request, $auth_user)
    {
         Auth::loginUsingId($auth_user->id_user);
    }
}
