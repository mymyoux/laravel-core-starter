<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;
use DB;

class Beanstalkd extends Model
{
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    const STATE_CREATED = "created";
    const STATE_EXECUTED = "executed";
    const STATE_EXECUTED_FRONT = "executed_front";
    const STATE_EXECUTED_NOW = "executed_now";
    const STATE_FAILED = "failed";
    const STATE_FAILED_PENDING_RETRY = "failed_pending_retry";
    const STATE_PENDING = "pending";
    const STATE_RETRYING = "retrying";
    const STATE_CANCELLED = "cancelled";
    const STATE_REPLAYING = "replaying";
    const STATE_REPLAYING_EXECUTED = "replayed";
    const STATE_REPLAYING_FAILED = "replay_failed";
    const STATE_EXECUTING = "executing";
    /**
     * DELETED by error
     */
    const STATE_DELETED = "deleted";


    protected $table = 'beanstalkd_log';
    protected $primaryKey = 'id';

    protected $fillable = ['id_beanstalkd', 'id_user', 'identifier', 'delay', 'json', 'state', 'tries', 'duration', 'priority', 'queue','cls'];
}
