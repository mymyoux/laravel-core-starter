<?php

namespace Core\Services;


class Stats
{
	public $api;
	public function __construct()
	{
		$this->api = [];
	}
	public function addAPIAnnotation($route, $annotation)
	{
		$uri = $route->uri();
		if(!isset($this->api[$uri]))
		{
			$this->api[$uri] = [];
		}
		if(!isset($this->api[$uri]["annotations"]))
		{
			$this->api[$uri]["annotations"] = [];
		}
		$this->api[$uri]["annotations"][] = $annotation->toArray();
	}
	public function addApiCall($route)
	{
		$uri = $route->uri();
		if(!isset($this->api[$uri]))
		{
			$this->api[$uri] = ["count"=>0];
		}
		if(!isset($this->api[$uri]['count']))
		{
			$this->api[$uri]['count'] = 0;
		}
		$this->api[$uri]["count"]++; 
	}
	public function getApiStats()
	{
		$stats = cleanObject($this->api);
		return $stats;
	}
}
