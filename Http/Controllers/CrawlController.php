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
     * @warning If crawl has state parsed - This call will do nothing
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
	 * @ghost\Param(name="type",required=true)
	 * @ghost\Param(name="url",required=true)
	 * @ghost\Param(name="curl",required=false)
	 * @ghost\Param(name="state",required=false,default="crawl_needs_login")
	 * @ghost\Param(name="uuid",required=false)
	 * @ghost\Param(name="data",required=false)
	 * @ghost\Param(name="binary",requirements="boolean", required=false)
	 * @ghost\Param(name="id_crawl_login",requirements="\d+",required=false)
	 * Adds a new crawl
	 */
	public function add(Request $request)
	{
		$crawl = new Crawl;
		$crawl->url = $request->input('url');
		if(strpos($crawl->url,"://") === False && strpos($crawl->url,"data:")!==0)
		{
			$crawl->url = "https://".$crawl->url;
		}
		$crawl->type = $request->input('type');
		$crawl->state = $request->input('state');
		$crawl->uuid = $request->input('uuid');
		$crawl->data = $request->input('data');
		$crawl->binary = $request->input('binary');
		$crawl->id_crawl_login = $request->input('id_crawl_login');
		dd($crawl);

 
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
