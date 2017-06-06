<?php

namespace Core\Connector;

class Slack extends ConnectorCore
{
	protected $token;
	protected $expiresIn;

	public $id; 
	public $name;
	public $nickname;
	public $email;
	public $avatar;
	public $id_slack_user;
	public $id_slack_team;
	public $team_name;


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
		if(isset($data->id))
		{
			$this->id_slack_user = $data->id;
		}
		if(isset($data->accessTokenResponseBody) )
		{
			$body = $data->accessTokenResponseBody;
			if(isset($body["user_id"]) && !isset($this->id))
			{
				$this->id = $body["user_id"];
				$this->id_slack_user = $body["user_id"];
			}
			if(isset($body["team_id"]) && !isset($this->id_slack_team))
			{
				$this->id_slack_team = $body["team_id"];
			}
			if(isset($body["team_name"]) && !isset($this->team_name))
			{
				$this->team_name = $body["team_name"];
			}
			if(isset($data->accessTokenResponseBody["team"]["id"]) )
			{
				$this->id_slack_team = $data->accessTokenResponseBody["team"]["id"];
			}
		}
		if(!isset($this->nickname))
			$this->nickname = $this->name;
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
