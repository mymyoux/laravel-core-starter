<?php

namespace Core\Model\Connector;

use Illuminate\Database\Eloquent\Model;

class Facebook extends Model
{
    protected $table = 'connector_facebook';
    protected $primaryKey = 'id_user';

    protected $fillable = ['id_user', 'id', 'nickname', 'name', 'email', 'avatar', 'verified', 'gender'];
}
