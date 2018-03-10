<?php

namespace Core\Http\Controllers;

use Illuminate\Http\Request;
use Core\Services\Test;
use App\User;
use Auth;
use Api;
use Core\Exception\ApiException;
use \Core\Api\Paginate;
use Core\Api\Annotations as ghost;
use Core\Model\UserLoginToken;
use Illuminate\Support\Facades\Route;
  /**
   * @ghost\Role("user")
   */
class UserController extends Controller
{
    /**
   * @ghost\Api
   * @ghost\Role("visitor")
   * @ghost\Middleware("\Illuminate\Session\Middleware\AuthenticateSession")
   
   * @return User logout
	 */
    public function logout(Request $request)
    {
        Auth::logout();
        return redirect('/');
    }
	/**
   * Get current user info
   * @ghost\Role("visitor")
   * @ghost\Middleware("\Illuminate\Session\Middleware\AuthenticateSession")
   * @return User current user
	 */
    public function me(Request $request)
    {
   		$user = Auth::user();
        if(!isset($user))
        {
            return null;
        }
        //TABLE:check les inserts
   		$result = Api::get('user/get-infos')->send(["id_user"=>$user->getKey().""]);

        if(isset($result))
        {
            $request = UserLoginToken::select('token')->where(["id_user"=>$result->getKey()])->first();

            if (!isset($request))
            {
                $token = generate_token();
                UserLoginToken::insert(["id_user"=>$result->getKey(),"token"=>$token]);
            }
            else
            $token = $request->token;

            $result->token = $token;
        }

        if (Auth::user()->isImpersonated())
        {
            $result->real_user = Auth::user()->getRealUser();
            $request = UserLoginToken::select('token')->where(["id_user"=>$result->real_user->getKey()])->first();
            
            if (!isset($request))
            {
                $token = generate_token();
                UserLoginToken::insert(["id_user"=>$result->real_user->getKey(),"token"=>$token]);
            }
            else
            $token = $request->token;

            $result->real_user->token = $token;
        }

        return $result;
    }
   /**
    * Get infos on specific user
	  * @ghost\Param(name="id_user",required=true,requirements="\d+",type="int")
    * @warning We will limit this access
    * @return User user
	  */
    public function getInfos(Request $request)
    {
    	$id_user = $request->input('id_user');
    	$user = User::find($id_user);
    	return $user;
    }
    /**
    * Get infos on specific user
	  * @ghost\Role("admin")
	  * @ghost\Param(name="id_user",required=true,requirements="\d+",type="int")
    * @warning We will limit this access
    * @return User user
	  */
    public function get(Request $request)
    {
    	$id_user = $request->input('id_user');
    	$user = User::find($id_user);
    	return $user;
    }
    /**
     * @ghost\Param(name="search")
     * @ghost\Paginate(allowed="id_user,created_time,updated_time,email,first_name,last_name,login",keys="first_name",directions="1", limit=10)
	 */
    public function list(Request $request, Paginate $paginate)
    {
        $search = $request->input('search');
        $request = User::where([]);
        if(isset($search))
        {
            if(is_numeric($search))
            {
                $request->where(["id_user"=>$search]);
            }else {
                if(User::hasColumn('first_name'))
                    $request->where('first_name', 'like', '%'.$search.'%');
                if(User::hasColumn('last_name'))
                    $request->orWhere('last_name', 'like', '%'.$search.'%');
                if(User::hasColumn('login'))
                    $request->orWhere('login', 'like', '%'.$search.'%');
                $request->orWhere('email', 'like', '%'.$search.'%');
                if(strpos($search," ")!==False)
                {
                    if(User::hasColumn('first_name') &&  User::hasColumn('last_name'))
                    {
                        $request->orWhere(function($query) use($search)
                        {
                            $parts = explode(" ", $search);
                            $first_name = array_shift($parts);
                            $query->where('first_name', 'like', '%'.$first_name.'%');
                            $query->where('last_name', 'like', '%'.join(" ",$parts).'%');
                        });
                    }
                }
            }
        }
        $request = $paginate->apply($request);
        return $request->get();
    }
}
