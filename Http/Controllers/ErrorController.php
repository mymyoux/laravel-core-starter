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
class ErrorController extends Controller
{
    /**
     * @ghost\Paginate(allowed="last_created_time,last_updated_time",keys="last_created_time",directions="-1", limit=10)
     * @return void
     */
    public function list(Request $request, Paginate $paginate)
    {
        $request = Error::select([DB::raw('COUNT(*) as count'),"id",DB::raw("MAX(error.created_time) as last_created_time"),"url","type","code","message","id_user","id_real_user","file","line",DB::raw("MAX(error.updated_time) as last_updated_time"),"ip",DB::raw("CONCAT(url,'-',file,'-',line,'-',type,'-',code) as identifier")])
        ->groupBy('identifier');

        $request = $paginate->apply($request);
        return $request->get();
    }
    /**
     * @ghost\Param(name="start",requirements="\d+", required=true)
     * @ghost\Param(name="end",requirements="\d+", required=true)
     * @ghost\Param(name="step",requirements="\d+", required=true)
     * @return void
     */
    public function interval(Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end');
        $step = $request->input('step');

        $start = date("Y-m-d H:i:s", $start);
        $end = date("Y-m-d H:i:s", $end);
        
        $request = Error::select([DB::raw('COUNT(*) as count'),"id",DB::raw("MAX(error.created_time) as last_created_time"),"url","type","code","message","id_user","id_real_user","file","line",DB::raw("MAX(error.updated_time) as last_updated_time"),"ip",DB::raw("CONCAT(url,'-',file,'-',line,'-',type,'-',code) as identifier")])
        ->where("created_time",">=",$start)
        ->where("created_time","<=",$end)
        ->groupBy([DB::raw("UNIX_TIMESTAMP(error.created_time) DIV ".$step)]);
        return $request->get();
    }
}