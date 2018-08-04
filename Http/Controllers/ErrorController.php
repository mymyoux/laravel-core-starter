<?php

namespace Core\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Auth;
use Api;
use Core\Exception\ApiException;
use \Core\Api\Paginate;
use Core\Api\Annotations as ghost;
use App;
use Notification;

use DB;
use Sheets;
use Google;
use Job;
use Core\Jobs\Test;
use Logger;
use Apiz;
use Core\Model\Error;

/**
 * @ghost\Role("admin")
 */
class ErrorController extends Controller
{
    /**
     * @ghost\Param(name="start",requirements="\d+", required=false, type="int")
     * @ghost\Param(name="end",requirements="\d+", required=false, type="int")
     * @ghost\Param(name="is_api", required=false, type="boolean", default=null)
     * @ghost\Param(name="front", required=false, type="boolean", default=false)
     * @ghost\Param(name="back", required=false, type="boolean", default=false)
     * @ghost\Paginate(allowed="last_created_at,last_updated_at,count,time",keys="last_created_at",directions="-1", limit=10)
     * @return void
     */
    public function list(Request $request, Paginate $paginate)
    {
        $javascript = DB::table('error_javascripts')->select([DB::raw('COUNT(*) as count'),"id_error",DB::raw("MAX(error_javascripts.created_at) as last_created_at"),"error_url","type","session","error_message","user_id","user_id",DB::raw("null as file"), "error_line",DB::raw("MAX(error_javascripts.updated_at) as last_updated_at"),DB::raw("null as ip"),DB::raw("CONCAT(SUBSTRING_INDEX(error_url,'?',1),'-',error_line,'-',type) as identifier")])
        ->groupBy('identifier');

        $req = Error::select([DB::raw('COUNT(*) as count'),"id",DB::raw("MAX(errors.created_at) as last_created_at"),"url","type","code","message","user_id","user_id_real","file","line",DB::raw("MAX(errors.updated_at) as last_updated_at"),"ip",DB::raw("CONCAT(SUBSTRING_INDEX(url,'?',1),'-',file,'-',line,'-',type,'-',code) as identifier")])
        ->where('errors.is_api', '=', false)
        ->groupBy('identifier');

        $req_api = Error::select([DB::raw('COUNT(*) as count'),"id",DB::raw("MAX(errors.created_at) as last_created_at"),"url","type","code","message","user_id","user_id_real","file","line",DB::raw("MAX(errors.updated_at) as last_updated_at"),"ip",DB::raw("CONCAT(SUBSTRING_INDEX(url,'?',1),'-',file,'-',line,'-',type,'-',code) as identifier")])
        ->where('errors.is_api', '=', true)
        ->groupBy('identifier');

        $start = $request->input('start');
        $end = $request->input('end');
        $is_api = $request->input('is_api');
        $back = $request->input('back');
        $front = $request->input('front');

        if(isset($start))
        {
            if(strlen((string)$start)==13)
            {
                $start = (int)($start/1000);
            }
            $start = date("Y-m-d H:i:s", $start);
            
            $req->where("errors.created_at",">=",$start);
            $req_api->where("errors.created_at",">=",$start);
            $javascript->where("error_javascripts.created_at",">=",$start);
        }
        if(isset($end))
        {
            if(strlen((string)$end)==13)
            {
                $end = (int)($end/1000);
            }
            $end = date("Y-m-d H:i:s",$end);
            
            $req->where("errors.created_at","<=",$end);
            $req_api->where("errors.created_at","<=",$end);
            $javascript->where("error_javascripts.created_at","<=",$end);
        }

        $request = null;

        if (!$back)
        {
            if ($is_api)
                $request = $req_api;
            if ($front)
                $request = $javascript;
        }
        else
        {
            $request = $req;
        }

        if (true === $is_api)
        {
            if ($request != $req_api)
                $request->union($req_api);
        }

        if (true === $front)
        {
            if ($request != $javascript)
                $request->union($javascript);
        }

        if (null === $request) return [];

        $request = $paginate->apply($request);

        return $request->get();
    }
    /**
     * @ghost\Param(name="start",requirements="\d+", required=true, type="int")
     * @ghost\Param(name="end",requirements="\d+", required=true, type="int")
     * @ghost\Param(name="step",requirements="\d+", required=true, type="int")
     * @return void
     */
    public function interval(Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end');
        $step = $request->input('step');
        if(strlen((string)$start)==13)
        {
            $start = (int)($start/1000);
        }
        if(strlen((string)$end)==13)
        {
            $end = (int)($end/1000);
        }
        $interval = [];
        for($i=$start; $i<=$end; $i+=$step)
        {
            $interval[] = $i;
        }
        $start = date("Y-m-d H:i:s", first($interval));
        $end = date("Y-m-d H:i:s", last($interval));
        
        $interval = collect($interval);

   
        $request = Error::select([DB::raw("(UNIX_TIMESTAMP(errors.created_at) DIV $step)*$step as time"),DB::raw('COUNT(*) as count'),"id",DB::raw("MAX(errors.created_at) as last_created_at"),"url","type","code","message","user_id","user_id_real","file","line",DB::raw("MAX(errors.updated_at) as last_updated_at"),"ip",
        DB::raw("CONCAT(SUBSTRING_INDEX(url,'?',1),'-',file,'-',line,'-',type,'-',code) as identifier")])
        ->where("created_at",">=",$start)
        ->where("created_at","<=",$end)
        ->groupBy([DB::raw("UNIX_TIMESTAMP(errors.created_at) DIV ".$step)]);
        $result = $request->get()->map(function($item)
        {
            //$item->count = rand(0,100000);
            return $item;
        });
        $interval->pop();
        $times =$result->pluck('time');
        $missings = $interval->diff($times);
        foreach($missings as $missing)
        {
            $result->push(std(["count"=>0/*rand(0,100000)*/,"time"=>$missing]));
        }
        return $result->sortBy('time')->values();
    }

    /**
     * @ghost\Param(name="url",type="string")
     * @ghost\Param(name="session",type="string")
     * @ghost\Param(name="error",type="array")
     * @ghost\Param(name="hardware",type="array")
     * @ghost\Role("visitor")
     * @return void
     */
    public function javascript( Request $request )
    {
        $url = $request->input('url');
        $session = $request->input('session');
        $error = $request->input('error');
        $hardware = $request->input('hardware');
        
        $user_id = Auth::check() ? Auth::user()->getKey() : null;
        $type = Auth::check() ? Auth::type() : null;
        $data = array("url"=>$url,"user_id"=>$user_id,"session"=>$session,"type"=>$type);


        $log = Error::recordJS($data, $hardware, $error);

        return $log;
    }
}
