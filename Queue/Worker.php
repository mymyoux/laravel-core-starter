<?php

namespace Core\Queue;

use Exception;
use Throwable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Queue\Worker as BaseWorker;
class Worker extends BaseWorker
{
    
}
