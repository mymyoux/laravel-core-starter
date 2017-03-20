<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;
use DB;

class Crawl extends Model
{
	/**
	 * Crawl on going
	 */
	const STATE_PENDING = "pending";
	/**
	 * Crawl failed
	 */
	const STATE_FAILED = "failed";
	/**
	 * Crawl done
	 */
	const STATE_DONE = "crawl_done";
	/**
	 * Have to be crawled
	 */
	const STATE_CREATED = "to_crawl";
	/**
	 * Parsing on going
	 */
	const STATE_PARSING = "parsing";
	/**
	 * Parsing failed, need recrawl logged
	 */
	const STATE_CRAWL_NEEDS_LOGIN = "crawl_needs_login";
	/**
	 * Crawl (loggued) on going
	 */
	const STATE_CRAWL_NEEDS_LOGIN_PENDING = "crawl_needs_login_pending";
	/**
	 * Crawl (loggued) failed
	 */
	const STATE_CRAWL_NEEDS_LOGIN_FAILED = "crawl_needs_login_failed";
	/**
	 * Parsing failed
	 */
	const STATE_PARSING_FAILED = "parsing_failed";
	/**
	 * Parsed
	 */
	const STATE_PARSED = "parsed";

	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';


    protected $table = 'crawl';
    protected $primaryKey = 'id_crawl';

    protected $fillable = ['state','value','extracted','tries'];

    public function attempt()
    {
    	 return $this->hasMany(Crawl::class);
    }
    public function createAttempt($data)
    {
    	
    }
}
