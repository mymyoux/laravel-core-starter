<?php

namespace Core\Model\Connector;

use Illuminate\Database\Eloquent\Model;

class Battlenet extends Model
{
    protected $fillable = ['user_id', 'nickname', 'name', 'email', 'avatar', 'id'];
}
