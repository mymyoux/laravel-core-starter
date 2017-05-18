<?php

namespace Core\Services;

use Core\Model\Crawl as CrawlModel;
use Api;

class Crawl
{
	public static function create( $url, $class, $type = null, $id_external = null, $id_crawl_login = null )
	{
		$params = [
			"url"=>trim($url),
			"tor"=>0,
			"priority"=>1,
			"type"=> $type,
			"cls"=> $class,
			"id_external"=>$id_external,
			'state' => 'crawl_needs_login',
			'asked' => 1,
			'data'=> "cabinet_needs_login",
			'id_crawl_login' => $id_crawl_login
		];

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
