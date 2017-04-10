<?php

namespace Core\Model\Connector;

use Illuminate\Database\Eloquent\Model;

class Discord extends Model
{
    protected $table = 'connector_discord';
    protected $primaryKey = 'id_user';

    protected $fillable = ['id_user', 'nickname', 'name', 'email', 'avatar', 'id', 'avatar_identifier', 'username', 'verified', 'mfa_enabled', 'discriminator'];
}
