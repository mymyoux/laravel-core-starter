<?php

namespace Core\Http\Middleware\Api;

use Closure;
use Core\Exception\ApiException;
use Illuminate\Http\Response;
use Api;
use Stats;
use Route;
use Request;
class Param
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $params)
    {
        $param = Api::unserialize($params);
        $route = Route::getFacadeRoot()->current();
        Stats::addAPIAnnotation($route, $param);

        $param->value = $request->input($param->name);
        $value = $param->validate($param->value);
        if(!isset($value) && isset($param->default))
        {
            $value = $param->default;
        }
        if(isset($param->type))
        {
            if (null !== $value)
            {
                if($param->array)
                {
                    foreach($value as &$v)
                    {
                        if ($param->type === 'boolean')
                            $v = filter_var($v, FILTER_VALIDATE_BOOLEAN);
                        else
                            settype($v, $param->type);
                    }
                }else
                {
                    if ($param->type === 'boolean')
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    else
                        settype($value, $param->type);
                }
            }
        }
        $input = array_merge(Request::input(), Request::file());
        if(!isset($input))
        {
            $input = [];
        }
        $input[$param->name] = $value;
        /*
        $request->query->set($param->name, $value);
        $request->request->set($param->name, $value);*/
        Request::replace($input);
        return $next($request);
    }
}
