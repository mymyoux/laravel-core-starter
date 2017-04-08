<?php

namespace Core\Connector;

class Steam extends ConnectorCore
{
	public $id;
	public $name;
	public $nickname;
	public $email;
	public $avatar;
	public $loccountrycode;
	public $profileurl;
	public $communityvisibilitystate;
	public $profilestate;
	public $lastlogoff;
	public $avatarfull;
	public $personastate;
	public $timecreated;
	public $personastateflags;

	public function readExternal( $data )
	{
		if (isset($data->user))
		{
			foreach ($data->user as $key => $value)
			{
				$this->{ $key } = $value;
			}

			$this->lastlogoff = date('Y-m-d H:i:s', $this->lastlogoff);
			$this->timecreated = date('Y-m-d H:i:s', $this->timecreated);
			unset($data->user);
		}

		parent::readExternal( $data );
	}

	public function getAccessToken()
	{
		return null;
	}

	public function getRefreshToken()
	{
		return null;
	}

	public function getExpiresIn()
	{
		return null;
	}

	public function getScopes()
	{
		return null;
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
        return $this->loccountrycode;
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
