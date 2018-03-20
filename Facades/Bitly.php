<?php


namespace Core\Facades;
use Illuminate\Support\Facades\Facade;
class Bitly extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'bitly';
    }
}
