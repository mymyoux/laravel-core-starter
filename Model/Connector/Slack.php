<?php

namespace Core\Model\Connector;

use Illuminate\Database\Eloquent\Model;

class Slack extends \Tables\Model\User\Connector\Slack
{
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    
    protected $fillable = ['user_id', 'id', 'nickname', 'name', 'email', 'avatar','id_slack_user','id_slack_team','team_name'];
}
