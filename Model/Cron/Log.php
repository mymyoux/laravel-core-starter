<?php

namespace Core\Model\Cron;

use Core\Database\Eloquent\Model;
use DB;

class Log extends \Tables\Model\Cron\Log//\Tables\Model\Cron\Log
{
	const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $table = 'cron_log';
    protected $primaryKey = 'log_id';

    protected $fillable = ['cron_id', 'status'];
}
