<?php

namespace Core\Http\Middleware\Api;

use Closure;
use Api;
use Request;
use Core\Exception\ApiException;

use CacheManager;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class Cache
{
    public function handle($request, Closure $next, $params)
    {
        if (!(CacheManager::driver()->getStore() instanceof \Illuminate\Cache\RedisStore))
        {
            return $next($request);
        }        
        $params     = Api::unserialize($params);
        $name       = $params->name;
        $keys       = $params->keys;
        $ids        = $params->ids;
        $invalid    = $params->invalid;
        $cache_key  = $name;

        if (is_array($ids))
        {
            foreach ($ids as $key)
                $cache_key .= '-' . $request->input( $key );
        }

        $base_key   = slug($cache_key);

        if (is_array($keys))
        {
            foreach ($keys as $key)
                $cache_key .= '-' . $request->input( $key );
        }

        $cache_key  = slug($cache_key);

        if (false === $invalid)
        {
            $value      = CacheManager::get( $cache_key );

            if ($value)
            {
                return $value;
            }
        }

        $response = $next($request);
        if(isset($response->exception) && !($response instanceof JsonResponse))
        {
            return $response;
        }
        $data = $response;
        if($response instanceof JsonResponse)
        {
            $data = $response->getOriginalContent();
        }

        if (true === $invalid)
        {
            CacheManager::invalidAPI($base_key);
            // $redis = CacheManager::connection();
            // $keys = $redis->sMembers($base_key);
            // foreach ($keys as $key)
            //     CacheManager::forget($key);
            // CacheManager::forget($name);
        }   
        else
        {
            CacheManager::cacheAPI($base_key, $cache_key, $data);
            // CacheManager::put( $cache_key, $data, Carbon::now()->addDays(1));
            // // add all keys to an array in order to clear everything
            // $redis = CacheManager::connection();
            // $redis->sAdd($base_key, $cache_key);
        }     
        
        return $data;
    }
    
}
