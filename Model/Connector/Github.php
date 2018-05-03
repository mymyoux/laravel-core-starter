<?php

namespace Core\Model\Connector;

use Illuminate\Database\Eloquent\Model;

class Github extends \Tables\Model\User\Connector\Github
{
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';
    
    protected $fillable = ['user_id', 'id', 'nickname', 'name', 'email', 'avatar','gender'];
}
