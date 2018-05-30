<?php

namespace Core\Discord;

use Illuminate\Support\Facades\Cache as CacheService;

use Logger;
use Discord\Discord;

use React\Promise\Deferred;
use Core\Model\Error as ErrorService;
use CacheManager;

class Bot
{
    // only use now to send action to redis
    // using node.js bot to connect with redis & discord
    static public function addAction($data)
    {
        $key    = 'discord-' . config('services.discord.bot.id');
        $redis  = CacheManager::connection();

        $redis->sAdd($key, json_encode($data));
    }
}
