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
  /**
   * @ghost\Role("user")
   */
class UserController extends Controller
{
	/**
   * Get current user info
   * @ghost\Role("visitor")
   * @return User current user
	 */
    public function me(Request $request)
    {
   		$user = Auth::user();
      if(!isset($user))
      {
          return null;
      }
   		$result = Api::get('user/get-infos')->send(["id_user"=>$user->id_user.""]);
        if(isset($result))
            $result->token = USER_LOGIN_TOKEN::select('token')->where(["id_user"=>$result->id_user])->first()->token;
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
}
