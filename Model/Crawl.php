<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;
use DB;

class Crawl extends Model
{
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';


    protected $table = 'crawl';
    protected $primaryKey = 'id_crawl';

    protected $fillable = ['state','value','extracted','tries'];
}
