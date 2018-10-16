<?php

namespace Core\Traits;

use Illuminate\Support\Facades\Cache;
trait Role
{
	public static $ROLE_ADMIN = "admin";
	protected static $ROLE_CONNECTED = "user";
	public static $ROLE_DISCONNECTED = "visitor";
	public $roles = []; 
	protected function addRole($role)
	{
		$this->roles[] = $role;
	}

	public function isNeg($role)
	{
		if (mb_strpos($role, 'no_') === 0)
			return true;

		return false;
	}

	public function hasRole($role, $strict = false)
	{
		if(empty($this->roles))
		{
			$this->loadRoles();
		}	
		if($role == static::$ROLE_CONNECTED && isset($this->id_user))
			return True;

		if ($strict)
			return in_array($role, $this->roles);

		// hasRole || isAdmin and is not a negative role like "no_email"
		return in_array($role, $this->roles) || (!$this->isNeg($role) && $role != static::$ROLE_DISCONNECTED && in_array(static::$ROLE_ADMIN, $this->roles));
	}
	public function removeRole($role)
	{
		$index =array_search($role, $this->roles);
		if($index !== False)
		{
			array_splice($thisroles, $index, 1);
			$this->roles = array_values($this->roles);
		}
	}
	protected function loadRoles()
	{
		//add roles
	}
	public static function bootRole()
	{
	}

	public function getRolesAttribute()
    {
        return $this->roles;
    }
}
