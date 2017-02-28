<?php

namespace Core\Services;
use Schema;
use Cache;
class Tables
{
	public function __construct()
	{
	}
	public function getColumns($tablename)
	{
		$table = $this->getTable($tablename);
		return $table["columns"];
	}
	protected function getTable($tablename)
	{
		$key = "table:".$tablename;
		$table = Cache::get($key);
		if(!isset($table))
		{
			$columns = Schema::getColumnListing($tablename);
			$table = ["columns"=>$columns];
			Cache::forever($key, $table);
		}
		return $table;
	}
}
