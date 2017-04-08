<?php

namespace Core\Model\Connector;

use Illuminate\Database\Eloquent\Model;

class Steam extends Model
{
    protected $table = 'connector_steam';
    protected $primaryKey = 'id_user';

    protected $fillable = ['id_user', 'id', 'nickname', 'name', 'email', 'avatar', 'loccountrycode', 'profileurl', 'communityvisibilitystate', 'profilestate', 'lastlogoff', 'avatarfull', 'personastate', 'timecreated', 'personastateflags'];
}
