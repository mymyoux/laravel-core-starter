<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;
use DB;

class Cron extends \Tables\Model\Cron
{
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    const STATE_PROCESSING = 'processing';

    protected $fillable = ['name', 'platform', 'status', 'last_execution_time', 'last_launch_date', 'crontab_config', 'user', 'cmd', 'server_log', 'options', 'directory'];
}
