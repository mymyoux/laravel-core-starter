<?php
namespace Core\Services;

use CacheManager;
use Logger;
use Core\Exception\ApiException;

class Bitly
{
    public function __construct()
    {
    	$this->client = new \GuzzleHttp\Client([

        ]);
    }

    public function post( $ressource, $_params = [] )
    {
        return $this->request('POST', $ressource, ['form_params' => $_params]);
    }

    public function put( $ressource, $_params = [] )
    {
        return $this->request('PUT', $ressource, ['json' => $_params]);
    }

    public function json( $ressource, $_params = [] )
    {
        return $this->request('POST', $ressource, ['json' => $_params]);
    }

    public function get( $ressource, $_params = [], $auth = true )
    {
        return $this->request('GET', $ressource, ['query' => $_params], $auth);
    }

    public function request( $method, $ressource, $_params, $auth = true )
    {
        $api_name = strtolower(substr(get_class($this), strrpos(get_class($this), '\\') + 1));
        $path   = $this->getPath();

        try
        {
        	$auth 	= $this->getAuthParam($method, $ressource, $_params);

        	foreach ($_params as $key => $value)
        	{
        		if (isset($auth[ $key ]))
        		{
        			$auth[ $key ] = array_merge( $auth[ $key ], $_params[$key] );
        		}
        		else
        			$auth[ $key ] = $_params[$key];
        	}
        	foreach ($auth as $key => $value)
        	{
        		if (isset($params[ $key ]))
        		{
        			$_params[ $key ] = array_merge( $_params[$key], $auth[$key] );
        		}
        		else
        			$_params[ $key ] = $auth[$key];
        	}

        	$params = $auth + $_params;

            Logger::normal('[' . $method . '] ' . $path . $ressource . ' ' . json_encode($params));

            $data = $this->client->{ strtolower($method) }($path . $ressource, $params);

            CacheManager::increment( 'api:' . $api_name . ':request' );
        }
        catch (\Exception $e)
        {
            CacheManager::increment( 'api:' . $api_name . ':error' );
            
           	return $this->manageError($e);
        }

        $data   = json_decode($data->getBody()->getContents());

        if ($data->status_code === 200)
            return $data->data;
        else
        {
            // do something with errors ?
        }

        return $data;
    }

    protected function manageError( $e )
    {
        Logger::error($e->getMessage());
        return null;
    }

    public function getAuthParam()
    {
        return [
            'query'   => ['access_token' => config('services.bitly.key')]
        ];
    }

    public function getPath()
    {
        return 'https://api-ssl.bitly.com/v3/';
    }

    public function create( $url, $return_url = false )
    {
        $result = $this->get('shorten', ['longUrl' => $url]);
        
        if ($return_url)
        {
            return $result->url;
        }

        return $result;
    }
}
