<?php

namespace Core\Model\User\Token;

use Core\Database\Eloquent\Model;
use DB;
use Core\Model\User;
use Core\Model\User\Token\History;

class One extends Model
{
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';
    const TOKEN_LIFETIME = 8467200000;//14*7*24*3600*1000; //2 weeks
    
    protected $table = 'user_one_token';
    protected $primaryKey = 'id_user_one_token';

    static public function isTokenValid($id_user, $token, $device_token)
    {
        $token = One::where('id_user', '=', $id_user)
                            ->where('token', '=', $token)
                            ->where('device_token', '=', $device_token)
                            ->first();
        if (!$row)
            return false;

        return true;
    }

    static public function isOneTokenValid($token)
    {
        $token = One::where('token', '=', $token)
                            ->first();
        if (!$row)
            return false;

        return true;
    }

    static public function getToken($apirequest)
    {
        One::where(function($query){
            $query->where('expired_time', '<=', DB::raw('NOW()'));
            $query->whereNotNull('expired_time');
        })
        ->orWhere(function($query){
            $query->where('count', '<=', 0);
            $query->whereNull('count');
        })->delete();

        try
        {
            DB::beginTransaction();

            $result = One::where('token', '=', $apirequest->input('token'))->first();
            //no token
            if (!$result)
                return null;

            if(isset($result->count) && is_numeric($result->count))
            {
                $result->count--;
                $result->save();
            }

            History::insert($result->id_user, $result->token, $result->source);
            // $this->table(TokenTable::TABLE_ONE_SHOT_HISTORY)->insert(array("id_user"=>$result["id_user"],"token"=>$result["token"],"source"=>$result["source"]));
            DB::commit();
            // Notifications::oneToken($result);
            return $result->id_user;
        }
        catch(\Exception $e)
        {
            DB::rollback();
            throw $e;
        }
        return NULL;


    }
    static public function createToken($apirequest)
    {
        $token      = generate_token(100);
        $count      = $apirequest->input('count');
        $id_user    = $apirequest->input('id_user');
        $expires_in = $apirequest->input('expires_in');
        $source     = mb_substr($apirequest->input('source'), 0, 255);

        $tokenuser  = User::find($id_user);
        if(!isset($tokenuser))
        {
            throw new ApiException("No user ".$id_user);
        }
        if ($tokenuser->type == "admin")
        {
            throw new ApiException("Can't create token for admin user");
        }
        
        $one = new One;

        $one->id_user = $id_user;
        $one->source = $source;
        $one->token = $token;
        $one->count = $count;
        $one->expired_time = DB::raw('NOW() + INTERVAL ' . $expires_in . ' SECOND');
        $one->save();

        return $one;
    } 
    public function removeToken($device_token, $token = NULL)
    {
        //TODO: remove token
        $where = array();
        if(isset($device_token))
        {
            $where["device_token"] = $device_token;
        }
        if(isset($token))
        {
            $where["token"] = $token;
        }
        if(!empty($where))
            $this->table()->delete($where);
    }
    /**
     * Generate a token for the user
     * @return string|Null Token generated or NULL if there is no id_user
     */
    public function generateUserToken()
    {
        if(!isset($this->session->id_user))
        {
            return FALSE;
        }
        $this->session->token = generate_token();
        $this->table()->delete(
            array(
                "id_user" => $this->session->id_user,
                "device_token" => $this->session->device_token
            )
        );
        $this->table()->insert(
            array(
                "id_user"=>$this->session->id_user,
                "token" => $this->session->token,
                "device_token" => $this->session->device_token,
                "expire" => timestamp() + TokenTable::TOKEN_LIFETIME
             )
        );

        return $this->session->token;
    }
  
    /**
     * Clean all expired tokens
     */
    public function cleanExpiredToken()
    {
        $count = $this->table()->count();

        $this->table()->delete(
            array(
                "expire <= ? " => timestamp()
            )
        );

        $count2 = $this->table()->count();

        return $count - $count2;
    }
}
