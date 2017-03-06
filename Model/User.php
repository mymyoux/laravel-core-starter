<?php

namespace Core\Model;

use Illuminate\Notifications\Notifiable;
use Core\Model\IModel;
use Core\Traits\Cached;
use DB;
use Core\Traits\Role;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;

use Core\Database\Eloquent\Model;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

use Core\Core\PseudoTrait;


use Tables\USER_ROLE;
use Tables\USER_LOGIN_TOKEN;
use Tables\USER as TUSER;
class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
     use Authenticatable, Authorizable, CanResetPassword;

    use Notifiable;
    use Cached;
    use Role;
    use PseudoTrait;



    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    protected $table = TUSER::TABLE;
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
            $builder->where(TUSER::deleted, '=', 0);
        });
    }


    protected $realUser;

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
            $roles = USER_ROLE::where([USER_ROLE::id_user=>$user->id_user])->get();
            foreach($roles as $role)
            {
                $user->addrole($role->role);
            }
        }
        return $user;
    }
    protected function prepareModel($user)
    {
        $user->addPseudoTrait($user->type);
        return $user;
    }
    public function setRealUser($user)
    {
        $this->realUser = $user;
    }
    protected function findByApiToken($token)
    {
        $token = USER_LOGIN_TOKEN::select(USER_LOGIN_TOKEN::id_user)
            ->where(USER_LOGIN_TOKEN::token,'=',$token)
            ->first();
        return static::getById($token->id_user);
    }
}
