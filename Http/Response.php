<?php
namespace Core\Http;

use Illuminate\Http\Response as BaseResponse;

class Response extends BaseResponse
{
    protected function shouldBeJson($content)
    {
        $parent = parent::shouldBeJson($content);
        if(!$parent)
        {
            if(is_bool($content))
            {
                return True;
            }
        }
        return $parent;
    }
}

