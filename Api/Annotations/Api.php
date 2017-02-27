<?php
namespace Core\Api\Annotations;
use Core\Exception\Exception;
use Core\Exception\ApiException;


/**
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class Api extends CoreAnnotation
{
    public static function getMiddleware()
    {
      return NULL;
    }

    protected function _parse($value, $request)
    {
        return null;
    }

    public function validate( $value )
    {
       return true;
    }
}
