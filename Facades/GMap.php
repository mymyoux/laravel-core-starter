<?php


namespace Core\Facades;
use Illuminate\Support\Facades\Facade;
class GMap extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'gmap';
    }
}
