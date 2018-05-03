<?php

namespace Core\Model\Connector;

use Illuminate\Database\Eloquent\Model;

class Steam extends Model
{
    protected $fillable = ['user_id', 'id', 'nickname', 'name', 'email', 'avatar', 'loccountrycode', 'profileurl', 'communityvisibilitystate', 'profilestate', 'lastlogoff', 'avatarfull', 'personastate', 'timecreated', 'personastateflags'];
}
