<?php

namespace Core\Model\Connector;

use Illuminate\Database\Eloquent\Model;

class Battlenet extends Model
{
    protected $table = 'connector_battlenet';
    protected $primaryKey = 'id_user';

    protected $fillable = ['id_user', 'nickname', 'name', 'email', 'avatar', 'id'];
}
