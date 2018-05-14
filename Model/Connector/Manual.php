<?php

namespace Core\Model\Connector;

use Illuminate\Database\Eloquent\Model;

class Manual extends \Tables\Model\Connector\Manual
{
    protected $fillable = ['user_id', 'email', 'password'];
}
