<?php

namespace Core\Model\Connector;

use Illuminate\Database\Eloquent\Model;

class Twitter extends \Tables\Model\Connector\Twitter
{
    protected $table = 'connector_twitter';
    protected $primaryKey = 'id_user';

    protected $fillable = ['id_user', 'id', 'nickname', 'name', 'email', 'avatar', 'followers_count', 'protected', 'friends_count', 'listed_count', 'favourites_count', 'utc_offset', 'time_zone', 'verified', 'statuses_count', 'lang', 'twitter_register'];
}
