<?php
namespace Core\Api\Annotations;
use Core\Exception\Exception;
use Core\Exception\ApiException;


/**
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class Paginate extends CoreAnnotation
{
    public function handle($config)
    {
    	
    }
}
