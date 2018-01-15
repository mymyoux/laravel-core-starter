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

use Db;
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
     * @ghost\Paginate(allowed="last_created_time,last_updated_time,count,time",keys="last_created_time",directions="-1", limit=10)
     * @return void
     */
    public function list(Request $request, Paginate $paginate)
    {
        $req = Error::select([DB::raw('COUNT(*) as count'),"id",DB::raw("MAX(error.created_time) as last_created_time"),"url","type","code","message","id_user","id_real_user","file","line",DB::raw("MAX(error.updated_time) as last_updated_time"),"ip",DB::raw("CONCAT(SUBSTRING_INDEX(url,'?',1),'-',file,'-',line,'-',type,'-',code) as identifier")])
        ->groupBy('identifier');
        $start = $request->input('start');
        $end = $request->input('end');
        if(isset($start))
        {
            if(strlen((string)$start)==13)
            {
                $start = (int)($start/1000);
            }
            $start = date("Y-m-d H:i:s", $start);
            $req = $req->where("error.created_time",">=",$start);
        }
        if(isset($end))
        {
            if(strlen((string)$end)==13)
            {
                $end = (int)($end/1000);
            }
            $end = date("Y-m-d H:i:s",$end);
            $req = $req->where("error.created_time","<=",$end);
        }
        $req = $paginate->apply($req);
        return $req->get();
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

   
        $request = Error::select([DB::raw("(UNIX_TIMESTAMP(error.created_time) DIV $step)*$step as time"),DB::raw('COUNT(*) as count'),"id",DB::raw("MAX(error.created_time) as last_created_time"),"url","type","code","message","id_user","id_real_user","file","line",DB::raw("MAX(error.updated_time) as last_updated_time"),"ip",
        DB::raw("CONCAT(SUBSTRING_INDEX(url,'?',1),'-',file,'-',line,'-',type,'-',code) as identifier")])
        ->where("created_time",">=",$start)
        ->where("created_time","<=",$end)
        ->groupBy([DB::raw("UNIX_TIMESTAMP(error.created_time) DIV ".$step)]);
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
}