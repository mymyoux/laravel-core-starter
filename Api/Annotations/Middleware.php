<?php
namespace Core\Api\Annotations;
use Core\Exception\Exception;
use Core\Exception\ApiException;


/**
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class Middleware extends CoreAnnotation
{
    public $middleware;
    public function handle($config)
    {
        $this->middleware = explode(",", $this->middleware);
        foreach($this->middleware as $middleware)
        {
            $config->middlewares[] = $middleware;
        }
    }
}
