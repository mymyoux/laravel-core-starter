<?php


namespace Core\Facades;
use Illuminate\Support\Facades\Facade;
class ConsoleLog extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'consolelog';
    }
}
