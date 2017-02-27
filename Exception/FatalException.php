<?php
/**
 * Created by PhpStorm.
 * User: jeremy.dubois
 * Date: 11/10/2014
 * Time: 19:15
 */

namespace Core\Exception;


class FatalException extends Exception
{
    public function toJsonObject()
    {
    	$data = parent::toJsonObject();
    	$data["fatal"] = True;
    	return $data;
    }
}
