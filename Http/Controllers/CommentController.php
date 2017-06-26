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
use Job;
use Logger;
use Illuminate\Support\Facades\Redis;
use Core\Model\CommentRelation;
use Core\Model\CommentRelationUser;
use Core\Model\Comment;
/**
 * ghost\Role('admin')
 */
class CommentController extends Controller
{
     /**
     * @ghost\Param(name="id_user",requirements="\d+", array=true)
     * @ghost\Param(name="id_relation",requirements="\d+")
     * @ghost\Param(name="comment", required=true)
     * @return JsonModel
     */
    public function create(Request $request)
    {
        $id_user = $request->input('id_user');
        $id_relation = $request->input('id_relation');
        $id_user = array_values(array_unique($id_user));
        if(empty($id_user) && !isset($id_relation))
        {
            throw new ApiException("id_user_or_id_relation_required");
        }
        $comment_text = $request->input('comment');
        if(mb_strlen(trim($comment_text)) == 0)
        {
            throw new ApiException("comment_empty");
        }
        DB::beginTransaction();
        try
        {
            $comment = new Comment;
            if(!empty($id_user))
            {
                if(count($id_user) > 1)
                {
                    sort($id_user);
                    $name = "user:".join("-",$id_user);
                    $relation = CommentRelation::where(["name"=>$name])->first();
                    if(!isset($relation))
                    {
                        $relation = new CommentRelation;
                        $relation->name = $name;
                        $relation->save();
                        foreach($id_user as $id)
                        {
                            $user = User::find($id);
                            if(!isset($user))
                            {
                                throw new ApiException('bad id_user:'.$id);
                            }
                            $relation_user = new CommentRelationUser;
                            $relation_user->user()->associate($user);
                            $relation_user->relation()->associate($relation);
                            $relation_user->save();
                        }
                    }
                    $comment->external()->associate($relation);
                }else {
                    $user = User::find($id_user[0]);
                    if(!isset($user))
                    {
                        throw new ApiException('bad_id_user:'.$id);
                    }
                    $comment->external()->associate($user);
                }
            }else
            {
                $relation = CommentRelation::find($id_relation);
                if(!isset($relation))
                {
                    throw new ApiException('bad_id_relation:'.$id);
                }
                $comment->external()->associate($relation);
            }
            $comment->comment = $comment_text;
            $comment->save();
            DB::commit();
        }
        catch(\Exception $e)
        {
            DB::rollBack();
            throw $e;   
        }
        return $relation;
    }

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

        $value = $request->input('value');
        if (is_array($value))
            $value = json_encode($value);
    	$result = $crawl->update(["value"=>$value,"state"=>$attempt->state]);

    	$data = $success?[]:["state"=>"failed"];
        if($crawl->version == "zend")
        {
            Job::createz('crawl-parse', array_merge($data,["id_crawl"=>$crawl->id_crawl]))->send();
        }else
            return CrawlService::parse( $crawl->id_crawl, $data );
    	// Job::createz('crawl-parse', array_merge($data,["id_crawl"=>$]))->send();
    }
	/**
     * @ghost\Param(name="type",required=true)
     * @ghost\Param(name="cls",required=true)
	 * @ghost\Param(name="url",required=true)
	 * @ghost\Param(name="curl",required=false)
	 * @ghost\Param(name="state",required=false,default="crawl_needs_login")
	 * @ghost\Param(name="uuid",required=false)
	 * @ghost\Param(name="data",required=false)
	 * @ghost\Param(name="binary",requirements="boolean", required=false, default=false)
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
        $crawl->cls = $request->input('cls');
        $crawl->type = $request->input('type');
		$crawl->state = $request->input('state');
		$crawl->uuid = $request->input('uuid');
		$crawl->data = $request->input('data');
		$crawl->binary = (int) $request->input('binary', 0);
		$crawl->id_crawl_login = $request->input('id_crawl_login');
        $crawl->version = 'laravel';

        $crawl->save();

        // push to extension
        Job::putRaw(config('app.env') . '-crawl-notify-node', ['id_crawl' => $crawl->getKey()]);

        return $crawl;
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
