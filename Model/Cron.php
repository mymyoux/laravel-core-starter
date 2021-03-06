<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;
use DB;

class Cron extends \Tables\Model\Cron//\Tables\Model\Cron
{
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    const STATE_PROCESSING = 'processing';
    const STATE_OK = 'ok';
    const STATE_KO = 'ko';

    protected $table = 'cron';
    protected $primaryKey = 'cron_id';

    protected $fillable = ['name', 'platform', 'status', 'last_execution_time', 'last_launch_date', 'crontab_config', 'user', 'cmd', 'server_log', 'options', 'directory'];
}
