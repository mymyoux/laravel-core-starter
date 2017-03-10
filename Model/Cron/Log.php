<?php

namespace Core\Model\Cron;

use Core\Database\Eloquent\Model;
use DB;

class Log extends Model
{
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    protected $table = 'cron_log';
    protected $primaryKey = 'log_id';

    protected $fillable = ['cron_id', 'status'];
}
