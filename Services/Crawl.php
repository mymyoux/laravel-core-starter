<?php

namespace Core\Services;

use Core\Model\Crawl as CrawlModel;
use Api;

class Crawl
{
	public static function create( $url, $class, $type = null, $config = null )
	{
		$params = [
			"url"=>trim($url),
			"tor"=>0,
			"priority"=>1,
			"type"=> $type,
			"cls"=> $class,
			'state' => 'crawl_needs_login',
			'asked' => 1,
			'data'=> "cabinet_needs_login",
		];

		if ($config)
		{
			if (isset($config['id_external']))
				$params['id_external'] = $config['id_external'];
			
			if (isset($config['id_crawl_login']))
				$params['id_crawl_login'] = $config['id_crawl_login'];
			
			if (isset($config['post_params']))
				$params['post_params'] = $config['post_params'];

			if (isset($config['referrer']))
				$params['referrer'] = $config['referrer'];
		}

    	$result = Api::post('crawl/add')->params( $params )->response();

		return $result;
	}

	public static function parse( $id_crawl, $data = [] )
	{
    	$crawl = CrawlModel::find($id_crawl);

    	if ($crawl)
			Job::create($crawl->cls, array_merge($data, ["id_crawl"=> $id_crawl]))->send();
	}
}
