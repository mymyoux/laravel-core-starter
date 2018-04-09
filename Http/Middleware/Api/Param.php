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
            if(class_exists($param->type))
            {
                $cls = $param->type;
                if(!isset($value))
                {
                    $model = NULL;
                }else
                {
                    $model = $cls::onRouteParam($value, $param);
                }
                if(isset($value) && ($param->flag_missing || $param->required) && !isset($model))
                {
                    throw new ApiException($param->name . " model linked not found", 10);
                }
                if($param->array)
                { 
                    if(!empty($value) && count($value) > $model->count() && ($param->flag_missing || $param->required))
                    {
                        $ids = $model->map(function($item){ return $item->getKey(); })->toArray();
                        $missing = array_diff($value, $ids);
                        throw new ApiException($param->name . " model linked not found - ".implode(",", $missing), 10);
                    }
                }
                if(!isset($param->prop))
                {
                    if(starts_with($param->name, "id_"))
                    {
                        $param->prop = substr($param->name, 3);
                    }else if(ends_with($param->name, "_id")){
                        $param->prop = substr($param->name, 0, -3);
                    }
                    if($param->array)
                    {
                        $param->prop.="s";
                    }
                }
                //$request->attributes->add([ $param->prop  => $model]);
                $param->type = "int";
            }
            
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
        $input = $request->all();
        if(!isset($input))
        {
            $input = [];
        }
        $input[$param->name] = $value;

        if(isset($param->prop))
        {
            if(isset($input[$param->prop]))
            {
                throw new ApiException($param->prop." already exists");
            }
            $input[$param->prop] = $model;
        }

        // need both in order to keep that working for sub API request 
        $request->replace($input);
        Request::replace($input);
        return $next($request);
    }
}
