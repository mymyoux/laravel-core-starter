<?php

namespace Core\Connector;

class Linkedin extends ConnectorCore
{
	protected $token;

	public $id;
    public $email;
    public $first_name;
    public $last_name;
    public $link;
    // public $pictureUrl;
    // public $formattedName;
    // public $emailAddress;
    
	public function readExternal( $data )
	{
		if (isset($data->user))
		{
            if (isset($data->user['publicProfileUrl']))
                $this->link = $data->user['publicProfileUrl'];
            if (isset($data->name))
            {
                $this->first_name   = mb_substr($data->name, 0, mb_strpos($data->name, ' '));
                $this->last_name    = mb_substr($data->name, mb_strpos($data->name, ' ') + 1);
            }

			unset($data->user);
		}

        parent::readExternal( $data );
	}

	public function getUsername()
    {
        return $this->name;
    }

    public function getAvatar()
    {
        return $this->avatar;
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
		return null;
	}

	public function getExpiresIn()
	{
		return $this->expiresIn;
	}
}
