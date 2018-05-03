<?php

namespace Core\Model\Connector;

use Illuminate\Database\Eloquent\Model;

class Twitter extends \Tables\Model\Connector\Twitter
{
    protected $fillable = ['user_id', 'id', 'nickname', 'name', 'email', 'avatar', 'followers_count', 'protected', 'friends_count', 'listed_count', 'favourites_count', 'utc_offset', 'time_zone', 'verified', 'statuses_count', 'lang', 'twitter_register'];
}
