<?php

namespace Core\Model\Connector;

use Illuminate\Database\Eloquent\Model;

class Coinbase extends \Tables\Model\User\Connector\Coinbase
{
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    
    protected $fillable = ['user_id', 'id', 'nickname', 'name', 'email', 'avatar','gender'];
}
