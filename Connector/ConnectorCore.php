<?php

namespace Core\Connector;
use Illuminate\Http\Response;
abstract class ConnectorCore
{
	protected $connector = null;
	protected $scopes = null;

	public function __construct( $data = [] )
	{
		$this->readExternal( $data );
	}

	public function readExternal( $data )
	{
		foreach ($data as $key => $value)
		{
			$this->{ $key } = $value;
		}
	}

	public function setConnectorModel( $connector )
	{
		if (null === $connector)
			throw new \Exception('Connector not exist in App\Core\ConnectorCore', 500);

		$this->connector = $connector;

		return $this;
	}

	public function getConnector()
	{
		return $this->connector;
	}

	public function toArray()
	{
		return to_array($this, $this);
	}

    // REGISTER USER FUNCTION
    abstract public function getUsername();

    public function isSocial()
    {
        return false;
    }

    public function getAvatar()
    {
        return null;
    }

    public function getCountry()
    {
        return null;
    }

    public function getFullName()
    {
        return null;
    }

    public function getEmail()
    {
        return null;
    }
    public function getGender()
    {
        return null;
    }
    public function setScopes($scopes)
    {
        $this->scopes = $scopes;
    }
    // END REGISTER USER FUNCTION

	abstract public function getAccessToken();
	abstract public function getRefreshToken();
	abstract public function getExpiresIn();
	public function getScopes()
    {
        return $this->scopes;
    }
}

function to_array($object, $init = NULL)
{
    if(is_array($object))
    {
        foreach($object as $key => $value)
        {
            $object[$key] = to_array($value);
        }
        $data = $object;
    }else
    if(is_object($object))
    {
        if($init === $object)
        {
            //decomposition
            $keys = get_class_vars(get_class($object));
            $data = array();
            foreach($keys as $key => $value)
            {
                if (!starts_with($key, "_")) {
                    $data[$key] = to_array($object->$key);
                }
            }
            if( method_exists($object, "getShortName"))
            {
                $short = "id_".$object->getShortName();
                if(isset($data["id"]))
                {
                    $data[$short] = $data["id"];
                }else
                if(isset($data[$short]))
                {
                    $data["id"] = $data[$short];
                }
            }
        }
        else
        {
            if(method_exists($object, "__toArray"))
            {
                $data = to_array($object->__toArray());
            }else
            if(method_exists($object, "toArray"))
            {
                $data = to_array($object->toArray());
            }else
            {
                $data = json_decode(json_encode($object), True);
            }
        }

    }else
    {
        return $object;
    }
    return $data;
}
