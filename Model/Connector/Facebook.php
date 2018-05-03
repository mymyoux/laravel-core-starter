<?php

namespace Core\Model\Connector;

use Illuminate\Database\Eloquent\Model;

class Facebook extends \Tables\Model\Connector\Facebook
{
    protected $fillable = ['user_id', 'id', 'nickname', 'name', 'email', 'avatar', 'verified', 'gender'];
}
