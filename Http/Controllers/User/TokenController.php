<?php

namespace Core\Http\Controllers\User;

use Illuminate\Http\Request;
use Core\Api\Annotations as ghost;
use Core\Model\User\Token\One;
use Core\Http\Controllers\Controller;

/**
* @ghost\Role("user")
*/
class TokenController extends Controller
{

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
    * @ghost\Param(name="token",required=true,type="string")
    * @ghost\Param(name="data",required=false,type="boolean",default=false)
    * @warning We will limit this access
    * @return User user
    */
    public function get(Request $request)
    {
    	$token      = $request->input('token');
    	$with_data  = $request->input('data');
        $data       = One::getToken($token, $with_data);
        
    	return $data;
    }

    
    /**
    * Get infos on specific user
    * @ghost\Param(name="count",required=true,type="int")
    * @ghost\Param(name="id_user",required=true,type="int")
    * @ghost\Param(name="expires_in",required=true,type="int")
    * @ghost\Param(name="source",required=true,type="string")
    * @ghost\Role("admin")
    * @warning We will limit this access
    * @return User user
    */
    public function create(Request $request)
    {
        $one = One::createToken($request);

        return $one->token;
    }
}
