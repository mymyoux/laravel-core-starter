<?php

namespace Core\Connector;

class Manual extends ConnectorCore
{

	public $first_name;
	public $last_name;
	public $email;
	public $password;

	public function readExternal( $data )
	{
		parent::readExternal( $data );
	}

	public function getUsername()
    {
        return $this->first_name.' '.$this->last_name;
    }

	public function getAccessToken()
	{
		return NULL;
	}

	public function getRefreshToken()
	{
		return NULL;
	}

	public function getExpiresIn()
	{
		return NULL;
	}

	public function getScopes()
	{
		return NULL;
	}
	public function toArray()
	{
		return ["email"=>$this->email, "password"=>password_hash($this->password, \PASSWORD_DEFAULT)];
	}
}
