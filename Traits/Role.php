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
	public function hasRole($role)
	{
		return in_array($role, $this->roles) || ($role != static::$ROLE_DISCONNECTED && in_array(static::$ROLE_ADMIN, $this->roles));
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
	public static function bootRole()
	{
		
	}
}
