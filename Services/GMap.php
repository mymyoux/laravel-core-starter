<?php

namespace Core\Services;

use Core\Model\Crawl as CrawlModel;
use Api;
use Logger;
class GMap
{
    private $path = 'https://maps.googleapis.com/maps/api/place/';
    private $api_key;
    public function __construct()
    {
        $this->client 	= new \GuzzleHttp\Client();
        $this->init();
    }

    public function init()
    {
        $this->api_key = config('services.gmap.api_key');
    }

    public function post( $ressource, $_params = [] )
    {
        return $this->request('POST', $ressource, ['body' => $_params]);
    }

    public function put( $ressource, $_params = [] )
    {
        return $this->request('PUT', $ressource, ['json' => $_params]);
    }

    public function json( $ressource, $_params = [] )
    {
        return $this->request('POST', $ressource, ['json' => $_params]);
    }

    public function get( $ressource, $_params = [] )
    {
        return $this->request('GET', $ressource, ['query' => $_params]);
    }

    public function request( $method, $ressource, $_params )
    {
        $path   = $this->path ;//'https://api.smartrecruiters.com/';

        try
        {
            $params = $_params;

            if (!isset($params['query']))
                $params['query'] = ['key' => $this->api_key];
            else
                $params['query']['key'] = $this->api_key;

            $ressource .= '/json';
            //Logger::normal('[' . $method . '] ' . $path . $ressource . ' ' . json_encode($params));

            $data = $this->client->{ strtolower($method) }($path . $ressource, $params);
        }
        catch (\Exception $e)
        {
            throw $e;
        }

        $data   = json_decode($data->getBody(), True);

        return $data;
    }

}
class GmapException extends \Exception
{

}
