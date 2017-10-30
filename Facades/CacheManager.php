<?php  

namespace Core\Facades;
use Illuminate\Support\Facades\Facade;

class CacheManager extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'cachemanager';
    }
}
