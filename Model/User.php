<?php

namespace Core\Model;

use Illuminate\Notifications\Notifiable;
use Core\Model\IModel;
use Core\Model\Event;
use Core\Traits\Cached;
use Core\Traits\CachedAuto;
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

use Core\Database\Eloquent\Editable;

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
    use Editable;
    use CachedAuto;
    use Role;
 //   use PseudoTrait;



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
        'first_name','last_name', 'type','email','login','picture','num_connection','temp',
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
        'deleted','temp','cgu_accepted','remember_token','num_connection','last_connection'
    ]; 
    public $appends = ['roles'];

	public function getRolesAttribute()
    {
        return $this->attributes["roles"] = $this->roles;
    }

    /**
     * Type
     * @var string
     */
    protected static function boot()
    {
        parent::boot();
        if (TUSER::hasColumn('deleted'))
        static::addGlobalScope('deleted', function (Builder $builder) {
            $builder->where(TUSER::deleted, '=', 0);
        });
    }

    protected $realUser;

   
    public function isAdmin()
    {
        return $this->type == "admin";
    }
    public function isRealAdmin()
    {
        return $this->getRealUser()->type == "admin";
    }
    public function getType()
    {
        return $this->type;
    }
    // protected function _getById($id)
    // {
    //     $user = static::find($id);
    //     if(isset($user))
    //     {
    //         $user->addRole($user->type);
    //         $user->addRole(static::$ROLE_CONNECTED);
    //         $roles = USER_ROLE::where([USER_ROLE::id_user=>$user->id_user])->get();
    //         foreach($roles as $role)
    //         {
    //             $user->addrole($role->role);
    //         }
    //     }
    //     return $user;
    // }
    protected function beforeCache()
    {
        $this->addRole($this->type);
        $this->addRole(static::$ROLE_CONNECTED);
        $roles = USER_ROLE::where([USER_ROLE::id_user=>$this->getKey()])->get();
        foreach($roles as $role)
        {
            $this->addRole($role->role);
        }
    }
    protected function prepareModel()
    {
        if(isset($this->traits[$this->type]))
        {
            $cls = $this->traits[$this->type];
            $this->mixin(new $cls);
        }
        //$this->addPseudoTrait($this->type);
        return $this;
    }
    public function setRealUser($user)
    {
        $this->realUser = $user;
    }
    public function getRealUser()
    {
        return isset($this->realUser)?$this->realUser:$this;
    }
    public function isImpersonated()
    {
        return isset($this->realUser);
    }
    protected function findByApiToken($token)
    {
        $key = str_replace("%token", $token, "user-token:%token");
        $id_user = Cache::get($key);
        if(!$id_user)
        {
            $token = USER_LOGIN_TOKEN::select(USER_LOGIN_TOKEN::id_user)
            ->where(USER_LOGIN_TOKEN::token,'=',$token)
            ->first();
            if(isset($token))
            {
                $id_user = $token->id_user;
                Cache::forever($key, $id_user);
            }
        }
        if(!isset($id_user))
        {
            return NULL;
        }
        return static::find($id_user);
    }
    protected function getAvailableTypes()
    {
        return USER::select('type')->distinct('type')->pluck('type')->filter(function($item){return isset($item);})->values()->toArray();
    }
    protected function getByEmail($email)
    {
        $id_user = UserConnector::where(["email"=>$email])->select("id_user")->pluck('id_user')->first();
        if($id_user === NULL)
        {
            $id_user = User::where(["email"=>$email])->select("id_user")->pluck('id_user')->first();
            if($id_user === NULL)
            {
                $new_email = clean_email($email);
                if($new_email != $email)
                {
                    return $this->getByEmail($new_email);
                }
                return NULL;
            }
        }
        return $this->find($id_user);
    }
    public function infos()
    {
        return $this->morphMany('Core\Model\Event', 'owner');
    }
    // public function employee()
    // {
    //     return $this->hasOne('App\Model\CompanyModelEmployee', 'id_user','id_user');
    // }
    //  public function company()
    // {
    //     return $this->hasMany('App\Model\CompanyModelEmployee', 'id_user','id_user');
    // }
    public function prehandleCache()
    {
        $this->prepareCache();
    }
}
