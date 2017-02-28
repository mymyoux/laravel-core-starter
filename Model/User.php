<?php

namespace Core\Model;

use Illuminate\Notifications\Notifiable;
use Core\Model\Model;
use Core\Model\IModel;
use Core\Traits\Cached;
use Illuminate\Foundation\Auth\User as Authenticatable;
use DB;
use Core\Traits\Role;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
class User extends Authenticatable
{
    use Notifiable;
    use Model;
    use Cached;
    use Role;


    const TABLE_USER = "user";
    const TABLE_USER_API_TOKEN = "user_login_token";
    const TABLE_USER_LOG = "user_log_connection";
    const TABLE_PASSWORD_LOST = "user_login_lost";
    const TABLE_LOGIN = "user_login_attempt";
    const TABLE_USER_ROLE = "user_role";
    const TABLE_FRONT_ALERT = "user_front_alert";
    const TABLE_REASON = "user_reason";


    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    protected $table = User::TABLE_USER;
     protected $primaryKey = 'id_user'; 
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name','last_name', 'email',
    ];
    protected $casts = [
        'deleted' => 'boolean',
        'temp' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted','temp','cgu_accepted'
    ];
    protected $appends = ["roles"];


    /**
     * Type
     * @var string
     */
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('deleted', function (Builder $builder) {
            $builder->where('deleted', '=', 0);
        });
    }
    public function getRolesAttribute()
    {
        return $thi->attributes["roles"] = $this->roles;
    }
    public function isAdmin()
    {
        return $this->type == "admin";
    }
    protected function _getById($id)
    {
        $user = static::find($id);
        if(isset($user))
        {
            $user->addRole($user->type);
            $user->addRole(static::$ROLE_CONNECTED);
            $roles = DB::table(User::TABLE_USER_ROLE)->where(["id_user"=>$user->id_user])->get();
            foreach($roles as $role)
            {
                $user->addrole($role->role);
            }
        }
        return $user;
    }
    protected function findByApiToken($token)
    {
        $token = DB::table(User::TABLE_USER_API_TOKEN)
            ->select('id_user')
            ->where('token','=',$token)
            ->first();
        return static::getById($token->id_user);
    }
}
