<?php

namespace Core\Facades;
use Illuminate\Support\Facades\Facade;

class Crawl extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'crawl';
    }
}
