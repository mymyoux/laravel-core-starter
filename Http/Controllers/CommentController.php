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
}
