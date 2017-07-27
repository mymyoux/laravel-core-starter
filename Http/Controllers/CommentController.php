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
     * @ghost\Param(name="objects",requirements="\d+", array=true)
     * @ghost\Param(name="id_relation",requirements="\d+")
     * @return JsonModel
     */
    public function list(Request $request, Paginate $paginate)
    {
        $objects = $request->input('objects');
        $id_relation = $request->input('id_relation');
        if(empty($objects) && !isset($id_relation))
        {
            throw new ApiException("objects_or_id_relation_required");
        }
        $request = Comment::select('comment.*')->with('user','relation.objects.external')
        ->join('comment_relation','comment_relation.id_comment_relation','=','comment.id_comment_relation')
        ->join('comment_relation_user','comment_relation.id_comment_relation','=','comment_relation_user.id_comment_relation')
        ->groupBy('comment.id_comment');

        if(!empty($objects))
        {
            $request->whereIn('comment_relation.id_comment_relation', function($query) use($objects)
            {
                $query->from('comment_relation_user')->select('comment_relation_user.id_comment_relation');
                
                foreach($objects as $object)
                {
                    $query->orWhere(function($query) use($object)
                    {
                        $query->where('comment_relation_user.external_id','=',(int)$object["id"]);
                        $query->where('comment_relation_user.external_type','=',$object["type"]);
                    });
                }
                $query
                ->groupBy('comment_relation_user.id_comment_relation')
                ->having(Db::raw('COUNT(DISTINCT comment_relation_user.id_comment_relation_user)'),'=',count($objects));
            });
            // $request->where(function($query) use($objects)
            // {
            //     foreach($objects as $object)
            //     {
            //         $query->orWhere(function($query) use($object)
            //         {
            //             $query->where('comment_relation_user.external_id','=',$object["id"]);
            //             $query->where('comment_relation_user.external_type','=',$object["type"]);
            //         });
            //     }
            // });
        }else
        {
            $request->where('comment.id_relation','=',$id_relation);
        }
        $request->orderBy('comment.created_time','ASC');
        return $request->get();
    }
    /**
     * @ghost\Param(name="objects",requirements="\d+", array=true)
     * @ghost\Param(name="id_relation",requirements="\d+")
     * @ghost\Param(name="id_comment",requirements="\d+", required=true)
     * @ghost\Param(name="comment", required=true)
     * @return JsonModel
     */
    public function update(Request $request)
    {
        $id_comment = $request->input('id_comment');
        $objects = $request->input('objects');
        $id_relation = $request->input('id_relation');
        if(empty($objects) && !isset($id_relation))
        {
            throw new ApiException("objects_or_id_relation_required");
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
            if(!empty($objects))
            {
                $name = $this->getName($objects);
                //$name = "user:".join("-",$id_user);
                $relation = CommentRelation::where(["name"=>$name])->first();
                if(!isset($relation))
                {
                    $relation = new CommentRelation;
                    $relation->name = $name;
                    $relation->save();
                    foreach($objects as $object)
                    {
                        $obj = $object["type"]::find($object["id"]);
                        if(!isset($obj))
                        {
                            throw new ApiException('bad_id:'.$object["id"]);
                        }
                        $relation_user = new CommentRelationUser;
                        $relation_user->external()->associate($obj);
                        $relation_user->relation()->associate($relation);
                        $relation_user->save();
                    }
                }
                $relation->load('objects.external');
                $comment->relation()->associate($relation);
            }else
            {
                $relation = CommentRelation::find($id_relation);
                if(!isset($relation))
                {
                    throw new ApiException('bad_id_relation:'.$id);
                }
                $relation->load('objects.external');
                $comment->relation()->associate($relation);
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
    protected function getName($objects)
    {
        usort($objects, function($a, $b)
        {
            if($a["type"]!=$b["type"])
            {
                return $b["type"]>$a["type"]?-1:1;
            }
             if($a["id"]!=$b["id"])
            {
                return $b["id"]>$a["id"]?-1:1;
            }
            return 0;
        });

        $type = NULL;
        $name = "";
        foreach($objects as $object)
        {
            if(!isset($type) || $type != $object["type"])
            {
                $type = $object["type"];
                $name.=$type.":";
            }
            $name.=$object["id"].",";
        }
        $name = substr($name, 0, -1);
        return $name;
    }
     /**
     * @ghost\Param(name="objects",requirements="\d+", array=true)
     * @ghost\Param(name="id_relation",requirements="\d+")
     * @ghost\Param(name="comment", required=true)
     * @return JsonModel
     */
    public function create(Request $request)
    {
        $objects = $request->input('objects');
        $id_relation = $request->input('id_relation');
        if(empty($objects) && !isset($id_relation))
        {
            throw new ApiException("objects_or_id_relation_required");
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
            if(!empty($objects))
            {
                $name = $this->getName($objects);
                //$name = "user:".join("-",$id_user);
                $relation = CommentRelation::where(["name"=>$name])->first();
                if(!isset($relation))
                {
                    $relation = new CommentRelation;
                    $relation->name = $name;
                    $relation->save();
                    foreach($objects as $object)
                    {
                        $obj = $object["type"]::find($object["id"]);
                        if(!isset($obj))
                        {
                            throw new ApiException('bad_id:'.$object["id"]);
                        }
                        $relation_user = new CommentRelationUser;
                        $relation_user->external()->associate($obj);
                        $relation_user->relation()->associate($relation);
                        $relation_user->save();
                    }
                }
                $relation->load('objects.external');
                $comment->relation()->associate($relation);
            }else
            {
                $relation = CommentRelation::find($id_relation);
                if(!isset($relation))
                {
                    throw new ApiException('bad_id_relation:'.$id);
                }
                $relation->load('objects.external');
                $comment->relation()->associate($relation);
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
