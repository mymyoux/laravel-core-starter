<?php

namespace Core\Connector;

class Battlenet extends ConnectorCore
{
	protected $token;
	protected $refreshToken;
	protected $expiresIn;

	public $name;
	public $nickname;
	public $id;
	public $email;
	public $avatar;

	public function getUsername()
    {
        return $this->nickname;
    }

	public function getAccessToken()
	{
		return $this->token;
	}

	public function getRefreshToken()
	{
		return null;
	}

	public function getExpiresIn()
	{
		return $this->expiresIn;
	}
}
