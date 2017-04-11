<?php

namespace Core\Connector;

class Twitter extends ConnectorCore
{
	protected $token;
	protected $tokenSecret;

	public $id;
	public $name;
	public $nickname;
	public $email;
	public $avatar;
	public $protected;
	public $followers_count;
	public $friends_count;
	public $listed_count;
	public $favourites_count;
	public $utc_offset;
	public $time_zone;
	public $verified;
	public $statuses_count;
	public $lang;
	public $twitter_register;

	public function readExternal( $data )
	{
		if (isset($data->user))
		{
			foreach ($data->user as $key => $value)
			{
				$this->{ $key } = $value;
			}

			$this->twitter_register = date('Y-m-d H:i:s', strtotime($this->created_at));

			unset($data->user);
		}

		parent::readExternal( $data );
	}

	public function getUsername()
    {
        return $this->nickname;
    }

    public function isSocial()
    {
        return true;
    }

	public function getAccessToken()
	{
		return $this->token;
	}

	public function getRefreshToken()
	{
		return $this->tokenSecret;
	}

	public function getExpiresIn()
	{
		return null;
	}
}
