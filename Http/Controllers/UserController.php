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
use Tables\USER_LOGIN_TOKEN;
use Tables\USER as TUSER;
use Illuminate\Support\Facades\Route;
  /**
   * @ghost\Role("user")
   */
class UserController extends Controller
{
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
   		$result = Api::get('user/get-infos')->send(["id_user"=>$user->getKey().""]);
      if(isset($result))
      {
        $request = USER_LOGIN_TOKEN::select('token')->where(["id_user"=>$result->getKey()])->first();

        if (!isset($request))
        {
          $token = generate_token();
          USER_LOGIN_TOKEN::insert(["id_user"=>$result->getKey(),"token"=>$token]);
        }
        else
          $token = $request->token;

        $result->token = $token;
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
    	$user = User::getById($id_user);
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
                if(TUSER::hasColumn('first_name'))
                    $request->where('first_name', 'like', '%'.$search.'%');
                if(TUSER::hasColumn('last_name'))
                    $request->orWhere('last_name', 'like', '%'.$search.'%');
                if(TUSER::hasColumn('login'))
                    $request->orWhere('login', 'like', '%'.$search.'%');
                $request->orWhere('email', 'like', '%'.$search.'%');
                if(strpos($search," ")!==False)
                {
                    if(TUSER::hasColumn('first_name') &&  TUSER::hasColumn('last_name'))
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
