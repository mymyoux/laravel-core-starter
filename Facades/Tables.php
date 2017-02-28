<?php  


namespace Core\Facades;
use Illuminate\Support\Facades\Facade;
class Tables extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'tables';
    }
}
