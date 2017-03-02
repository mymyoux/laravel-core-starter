<?php

namespace Core\Http;
use Illuminate\Routing\Router;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
     //TODO: update this on laravel upgrade 
    //
    //
    //
    protected $bootstrappers = 
    [
       \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Core\App\MultiEnvironmentLoadConfiguration::class,
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
        \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
        \Illuminate\Foundation\Bootstrap\BootProviders::class,
    ];
   
}
