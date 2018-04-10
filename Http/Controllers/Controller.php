<?php

namespace Core\Http\Controllers;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Core\Http\Response;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    public function callAction($method, $parameters)
    {
        $result =  call_user_func_array([$this, $method], $parameters);
        if(is_bool($result))
        {
            $result = new Response($result);
        }
        return $result;
    }
}
