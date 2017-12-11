<?php

namespace Core\Services;

use Illuminate\Support\Facades\Cache as CacheService;
use Carbon\Carbon;
use Logger;

class CacheManager
{
    public function __call ( $name , $arguments )
    {
        return CacheService::{ $name }(...$arguments);
    }

    public static function __callStatic ( $name , $arguments )
    {
        return CacheService::{ $name }(...$arguments);
    }

    public function invalidAPI($base_key)
    {
        if (!(CacheManager::driver()->getStore() instanceof \Illuminate\Cache\RedisStore))
            return;

        $redis = CacheManager::connection();
        
        $keys = $redis->sMembers($base_key);
        
        foreach ($keys as $key)
        {
            Logger::normal('api:forget cache ' . $key);
            CacheManager::forget($key);
        }
        
        Logger::normal('api:forget cache ' . $base_key);

        CacheManager::forget($base_key);
    }

    public function cacheAPI($cache_key, $base_key, $data, $days = 1)
    {
        CacheManager::put( $cache_key, $data, Carbon::now()->addDays( $days ));
        Logger::normal('api:hit cache ' . $cache_key);
        
        // add all keys to an array in order to clear everything
        $redis = CacheManager::connection();
        $redis->sAdd($base_key, $cache_key);
    }
}
