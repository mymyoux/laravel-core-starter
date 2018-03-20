<?php

namespace Core\Model;

use Illuminate\Notifications\Notifiable;
use Core\Model\IModel;
use Core\Model\Event;
use Core\Traits\Cached;
use Core\Traits\CachedAuto;
use DB;
use Core\Traits\Role as RoleTrait;
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

use Core\Model\UserLoginToken;
use Auth;

class User extends \Tables\Model\User implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
     use Authenticatable, Authorizable, CanResetPassword;

    use Notifiable;
    use Editable;
    use CachedAuto;
    use RoleTrait;
 //   use PseudoTrait;



    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name','last_name', 'type','email','login','picture','num_connection','temp',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'temp','cgu_accepted','remember_token','num_connection','last_connection'
    ]; 
    public $appends = ['roles'];

    /**
     * Type
     * @var string
     */
    protected static function boot()
    {
        parent::boot();
        if (static::hasColumn('deleted'))
        static::addGlobalScope('deleted', function (Builder $builder) {
            if(!Auth::isAdmin())
            {
                $builder->where("user.deleted", '=', 0);
            }
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

    protected function beforeCache()
    {
        $this->loadRoles();
    }
    protected function loadRoles()
	{
        $this->addRole($this->type);
        $this->addRole(static::$ROLE_CONNECTED);
        $roles = Role::where(["user_role.id_user"=>$this->getKey()])->get();
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
            $token = UserLoginToken::select("id_user")
            ->where("token",'=',$token)
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
    protected $_api_token;
    public function getApiTokenAttribute()
    {
        if(!isset($this->_api_token)){
            $this->_api_token = DB::table('user_login_token')->where('user_login_token.id_user','=',$this->getKey())->first()->token;
        }
        return $this->_api_token;
    }
    protected function getAvailableTypes()
    {
        return DB::table('user')->select('type')->distinct('type')->pluck('type')->filter(function($item){return isset($item);})->values()->toArray();
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

    public function prehandleCache()
    {
        $this->prepareCache();
    }
    public function getFirstNameAttribute()
    {
        if (isset($this->attributes["first_name"]) && isset($this->attributes["deleted"]))
        {
            if($this->deleted == 1)
            {
                return $this->attributes["first_name"]." (suspended)";
            }else
            {
                return $this->attributes["first_name"];
            }
        }
    }
}
