<?php
namespace Core\Api\Annotations;
use Core\Exception\Exception;
use Core\Exception\ApiException;


/**
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class Route extends CoreAnnotation
{
    public $path;
    public function handle($config)
    {
        $config->path = $this->path;
    }
}
