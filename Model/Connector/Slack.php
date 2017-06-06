<?php

namespace Core\Model\Connector;

use Illuminate\Database\Eloquent\Model;

class Slack extends Model
{
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';
    
    protected $table = 'user_connector_slack';
    protected $primaryKey = 'id_user';

    protected $fillable = ['id_user', 'id', 'nickname', 'name', 'email', 'avatar','id_slack_user','id_slack_team','team_name'];
}
