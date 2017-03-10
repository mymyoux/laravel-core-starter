<?php


namespace Core\Facades;
use Illuminate\Support\Facades\Facade;
class Job extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'job';
    }
}
