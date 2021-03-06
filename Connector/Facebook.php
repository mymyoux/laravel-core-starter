<?php

namespace Core\Connector;

class Facebook extends ConnectorCore
{
	protected $token;
	protected $expiresIn;

	public $id;
	public $name;
	public $nickname;
	public $email;
	public $avatar;
	public $verified;
	public $gender;

	public function readExternal( $data )
	{
		if (isset($data->user))
		{
			foreach ($data->user as $key => $value)
			{
				$this->{ $key } = $value;
			}

			unset($data->user);
		}

		parent::readExternal( $data );
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


	public function isSocial()
    {
        return true;
    }

	// REGISTER USER
	public function getUsername()
    {
        return isset($this->nickname) ? $this->nickname : preg_replace('/[^a-z]/', '', mb_strtolower($this->name));
    }

    public function getAvatar()
    {
        return $this->avatar;
    }

    public function getCountry()
    {
        return null;
    }

    public function getFullName()
    {
        return $this->name;
    }

    public function getEmail()
    {
        return $this->email;
    }
}
