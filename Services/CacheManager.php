<?php

namespace Core\Services;

use Illuminate\Support\Facades\Cache as CacheService;
use Carbon\Carbon;

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
            CacheManager::forget($key);

        CacheManager::forget($base_key);
    }

    public function cacheAPI($cache_key, $base_key, $data, $days = 1)
    {
        CacheManager::put( $cache_key, $data, Carbon::now()->addDays( $days ));
        
        // add all keys to an array in order to clear everything
        $redis = CacheManager::connection();
        $redis->sAdd($base_key, $cache_key);
    }
}
