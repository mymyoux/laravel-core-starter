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
     * @return JsonModel
     */
    public function list(Request $request, Paginate $paginate)
    {
        $id_user = $request->input('id_user');
        $id_relation = $request->input('id_relation');
        if(isset($id_user))
            $id_user = array_values(array_unique($id_user));
        if(empty($id_user) && !isset($id_relation))
        {
            throw new ApiException("id_user_or_id_relation_required");
        }

        $request = Comment::select('comment.*','comment_relation.id_comment_relation')->leftJoin('user', function ($join) {
            $join
            ->on('comment.external_type','=',Db::raw("'App\\\\User'"))
            ->on('comment.external_id','=','user.id_user');
        })->leftJoin('comment_relation', function ($join) {
            $join
            ->on('comment.external_type','=',Db::raw("'Core\\\\Model\\\\CommentRelation'"))
            ->on('comment.external_id','=','comment_relation.id_comment_relation');
        });
        if(!empty($id_user))
        {
            $request = $request->leftJoin('comment_relation_user', function ($join) use($id_user){
                $join
                ->on('comment_relation.id_comment_relation','=','comment_relation_user.id_comment_relation')
                ->whereIn('comment_relation_user.id_user', $id_user);
            });

        }
        
        if(isset($id_relation))
        {
            $request = $request->where("comment_relation.id_comment_relation", $id_relation);
        }else
        {
            $request = $request->where(function($query) use($id_user)
            {
                $query->whereIn("comment.external_id",$id_user)
                ->where("comment.external_type", "=",Db::raw("'App\\\\User'"));
            })->orWhere(function($query) use($id_user)
            {
                $query->whereIn("comment_relation_user.id_user",$id_user);
            });
        }
        $request->with('external','user');
        $request->orderBy('comment.created_time','ASC');

        $objects = $request->get();

        $with_relations = $objects->filter(function($item)
        {
            return $item->external_type == CommentRelation::class;
        })->load('external.users.user.employee.company.informations');
        
        return $objects;
    }
    /**
     * @ghost\Param(name="id_user",requirements="\d+", array=true)
     * @ghost\Param(name="id_relation",requirements="\d+")
     * @ghost\Param(name="id_comment",requirements="\d+", required=true)
     * @ghost\Param(name="comment", required=true)
     * @return JsonModel
     */
    public function update(Request $request)
    {
        $id_user = $request->input('id_user');
        $id_relation = $request->input('id_relation');
        $id_comment = $request->input('id_comment');
        if(isset($id_user))
            $id_user = array_values(array_unique($id_user));
        if(empty($id_user) && !isset($id_relation))
        {
            throw new ApiException("id_user_or_id_relation_required");
        }
        if(!isset($id_comment))
        {
            throw new ApiException('id_comment_required');
        }
        $comment_text = $request->input('comment');
        if(mb_strlen(trim($comment_text)) == 0)
        {
            throw new ApiException("comment_empty");
        }
        DB::beginTransaction();
        try
        {
            $comment = Comment::find($id_comment);
            if(!isset($comment))
            {
                throw new ApiException('id_comment_required');
            }
            $comment->user()->associate(Auth::user());
            if(!empty($id_user))
            {
                if(count($id_user) > 1)
                {
                    sort($id_user);
                    $name = "user:".join("-",$id_user);
                    $relation = CommentRelation::where(["name"=>$name])->with('users.user.employee.company.informations')->first();
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
                    $relation->load('users.user.employee.company.informations');
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
                $relation->load('users.user.employee.company.informations');
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
        return $comment;
    }
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
        if(isset($id_user))
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
            $comment->user()->associate(Auth::user());
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
                    $relation->load('users.user.employee.company.informations');
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
                $relation->load('users.user.employee.company.informations');
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
        return $comment;
    }
}
