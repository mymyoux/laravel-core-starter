<?php

namespace Core\Connector;

class Discord extends ConnectorCore
{
	protected $token;
	protected $refreshToken;
	protected $expiresIn;
	protected $scope;

	public $name;
	public $nickname;
	public $id;
	public $email;
	public $avatar;
	public $avatar_identifier;
	public $username;
	public $verified;
	public $mfa_enabled;
	public $discriminator;

	public function readExternal( $data )
	{
		if (isset($data->user))
		{
			foreach ($data->user as $key => $value)
			{
				$this->{ $key } = $value;
			}

			$this->avatar_identifier = $this->avatar;

			unset($data->user);
		}

		if (isset($data->accessTokenResponseBody) && isset($data->accessTokenResponseBody['scope']))
			$this->scope = $data->accessTokenResponseBody['scope'];

		parent::readExternal( $data );
	}

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
		return $this->refreshToken;
	}

	public function getExpiresIn()
	{
		return $this->expiresIn;
	}

	public function getScopes()
	{
		return $this->scope;
	}
}
