<?php
namespace Core\Api\Annotations;
use Core\Exception\Exception;
use Core\Exception\ApiException;


/**
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class Back extends CoreAnnotation
{
    public function handle($config)
    {
        parent::handle($config);
    }
}
