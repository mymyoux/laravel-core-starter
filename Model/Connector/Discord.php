<?php

namespace Core\Model\Connector;

use Illuminate\Database\Eloquent\Model;

class Discord extends Model
{
    protected $fillable = ['user_id', 'nickname', 'name', 'email', 'avatar', 'id', 'avatar_identifier', 'username', 'verified', 'mfa_enabled', 'discriminator'];
}
