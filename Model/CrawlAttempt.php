<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;
use DB;

class CrawlAttempt extends Model
{
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';


    protected $table = 'crawl_attempt';
    protected $primaryKey = 'id_crawl_attempt';

    protected $fillable = ['id_crawl','ip','type','login','uuid','state'];

    public function crawl()
    {
    	 return $this->belongsTo(Crawl::class);
    }
}
