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
			foreach ($config as $key => $value)
			{
				$params[$key] = $value;
			}
		}

    	$result = Api::post('crawl/add')->params( $params )->response();

		return $result;
	}

	public static function parse( $id_crawl, $data = [] )
	{
    	$crawl = CrawlModel::find($id_crawl);
		if ($crawl)
		{
			$cls = $crawl->cls;
			if (!$crawl->cls && $crawl->type)
			{
				$cls = '\App\Jobs\Crawl\\' . $crawl->type;
			}

			Job::create($cls, array_merge($data, ["id_crawl"=> $id_crawl]))->sendNow();
		}
	}
}
