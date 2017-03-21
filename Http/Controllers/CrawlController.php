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

use Core\Model\Crawl;
use Core\Model\CrawlAttempt;
use Illuminate\Support\Facades\Redis;
class CrawlController extends Controller
{
	/**
	 * Update a crawl record
     * @ghost\Param(name="id_crawl",requirements="\d+",required=true)
     * @ghost\Param(name="version",requirements="\d+",required=true)
     * @ghost\Param(name="success",requirements="boolean", required=true)
     * @ghost\Param(name="login",required=true)
     * @ghost\Param(name="value",required=true)
     * @ghost\Param(name="uuid",required=true)
     * @notice To be implemented
	 */
    public function update(Request $request)
    {
    	$crawl = Crawl::find($request->input("id_crawl"));
    	if(!$crawl)
    	{
    		throw new ApiException('bad_id_crawl');
    	}
    	if($crawl->state == Crawl::STATE_PARSED)
    	{
    		//we don't reparse already parsed crawl
    		return;
    	}
    	$success = $request->input('success');

    	$attempt = new CrawlAttempt;
    	$attempt->id_crawl = $crawl->id_crawl;
    	$attempt->ip = App::ip();
    	$attempt->type = $crawl->type;
    	$attempt->login = $request->input('login');
    	$attempt->uuid = $request->input('uuid');
    	$attempt->state = $success?Crawl::STATE_DONE:Crawl::STATE_CRAWL_NEEDS_LOGIN_FAILED;

    	$attempt->save();
    	$crawl->increment('tries');
    	$result = $crawl->update(["value"=>$request->input('value'),"state"=>$attempt->state]);

    	$data = $success?[]:["state"=>"failed"];
    	Job::createz('crawl-parse', array_merge($data,["id_crawl"=>$crawl->id_crawl]))->send();
    } 
    /**
     * Get some stats from crawl server
     * @ghost\Role("admin")
     */
    public function serverStats(Request $request)
    {
    	return ["users"=>(int)Redis::get('server:nodejs:users'),'jobs'=>(int)Redis::get('server:nodejs:jobs')];
    }
}
