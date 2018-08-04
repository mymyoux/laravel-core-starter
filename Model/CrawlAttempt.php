<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;
use DB;

class CrawlAttempt extends \Tables\Model\Crawl\Attempt
{
	const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $table = 'crawl_attempt';
    protected $primaryKey = 'id_crawl_attempt';

    protected $fillable = ['id_crawl','ip','type','login','uuid','state'];

    public function crawl()
    {
    	 return $this->belongsTo(Crawl::class);
    }
}
