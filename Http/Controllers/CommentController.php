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
use App\Model\Comment\CommentStateModel;
use App\Model\Cabinet\EmployeeModel;

/**
 * @ghost\Role("admin")
 */
class CommentController extends Controller
{
     /**
     * @ghost\Param(name="objects",requirements="\d+", array=true)
     * @ghost\Param(name="id_relation",requirements="\d+")
     * @ghost\Param(name="types",array=true,required=false)
     * @return JsonModel
     */
    public function list(Request $request, Paginate $paginate)
    {
        $objects = $request->input('objects');
        $id_relation = $request->input('id_relation');
        $types = $request->input('types');
        $user = Auth::getUser();
        if(empty($objects) && !isset($id_relation))
        {
            throw new ApiException("objects_or_id_relation_required");
        }
        if(isset($types) && !is_array($types))
        {
            $types = [$types];
        }

        $request = Comment::select('comment.*')->with('user','relation.objects.external')
        ->join('comment_relation','comment_relation.id_comment_relation','=','comment.id_comment_relation')
        ->join('comment_relation_user','comment_relation.id_comment_relation','=','comment_relation_user.id_comment_relation')
        //->orderBy('comment.created_at', 'DESC')
        ->groupBy('comment.id_comment');

        if(!empty($objects))
        {
            $request->whereIn('comment_relation.id_comment_relation', function($query) use($objects)
            {
                $query->from('comment_relation_user')->select('comment_relation_user.id_comment_relation');

                if ($objects[0]['id'] == Auth::id()) // get all comments from a cabinet
                {
                    $query->orWhere(function($query) use($objects)
                    {
                        $query->where('comment_relation_user.external_id','=',(int)$objects[0]["id"]);
                        $query->where('comment_relation_user.external_type','=',$objects[0]["type"]);
                    });

                    $query
                        ->groupBy('comment_relation_user.id_comment_relation');

                }
                else { //get comments of a specific candidate cabinet

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
                        ->having(DB::raw('COUNT(DISTINCT comment_relation_user.id_comment_relation_user)'),'=',count($objects))
                        ;
                }
            });
        }else
        {
            $request->where('comment.id_relation','=',$id_relation);
        }
        if(!empty($types))
        {
            $request->whereIn('comment.access_type', $types);
        }

        $create_state = False;
        if ($user->isCabinetEmployee())
        {
            $create_state = True;
            $comment_state = CommentStateModel::where(["id_user_cabinet"=>$user->id_user])
                ->where('id_user', '=', $user->id_user)
                ->first();
        }
        else {
            if($objects[0]["type"] == User::class)
            {
                $relationUser = User::find($objects[0]["id"]);
                if($relationUser->isCabinetEmployee())
                {
                    $create_state  =true;
                    $comment_state = CommentStateModel::where(["id_user_cabinet"=>$objects[0]["id"]])
                        ->where('id_user', '=', Auth::getUser()->id_user)
                        ->first();
                }
            }
        }
        if($create_state)
        {
            if (!isset($comment_state))
            {
                $comment_state = new CommentStateModel;
    
                if ($user->isCabinetEmployee())
                    $comment_state->id_user_cabinet = Auth::getUser()->id_user;
                else
                    $comment_state->id_user_cabinet = $objects[0]["id"];
    
                $comment_state->id_user = Auth::getUser()->id_user;
            }
    
            $comment_state->read_time = date('Y-m-d H:i:s');
            $comment_state->save();
        }

        $request->orderBy('comment.created_at','ASC');

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
        $type = $request->input('type');
        if(!isset($type))
        {
            $type = Auth::type();
        }
        if(is_array($type))
        {
            $type = $type[0];
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
                    $relation->count = count($objects);
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
            $comment->access_type = $type;
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
     * @ghost\Param(name="type", required=false)
     * @return JsonModel
     */
    public function create(Request $request)
    {
        $objects = $request->input('objects');
        $exists = [];

        $objects = array_values(array_filter($objects, function($item) use(&$exists)
        {
            $name = $item["type"].$item["id"];
            if(in_array($name, $exists))
                return False;
            $exists[] = $name;
            return True;
        }));
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
        $type = $request->input('type');
        if(!isset($type))
        {
            $type = Auth::type();
        }
        if(is_array($type))
        {
            $type = $type[0];
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

                $user = Auth::getUser();
                $user_destination = $objects[0]["id"];

                if ($user->isCabinetEmployee())
                    $user_destination = $user->id_user;

                $comment_state = CommentStateModel::where('id_user', '=', $user->id_user)
                    ->where('id_user_cabinet', '=', $user_destination)
                    ->first();

                if ($objects[0]["type"] != 'App\Model\CompanyModel')
                {
                    if (!isset($comment_state))
                    {
                        $comment_state = new CommentStateModel;

                        if ($user->isCabinetEmployee())
                            $comment_state->id_user_cabinet = Auth::getUser()->id_user;
                        else
                            $comment_state->id_user_cabinet = $objects[0]["id"];

                        $comment_state->id_user = Auth::getUser()->id_user;
                        $comment_state->read_time = date('Y-m-d H:i:s');
                    }
                    $comment_state->created_at = date('Y-m-d H:i:s');
                    $comment_state->save();
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
            $comment->access_type = $type;
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
     * Get notification for a cabinet
     * @ghost\Role("user")
     * @ghost\Api
     * @return  array
     */
    public function notification(Request $request)
    {
        $id_user = Auth::id();

        $request = CommentStateModel::select("comment_state.id", "comment_state_me.read_time", "comment_state.created_at")
            ->join('comment_state as comment_state_me', 'comment_state_me.id_user', '=', DB::raw('"' . Auth::getUser()->id_user . '"'))
            ->where('comment_state.id_user_cabinet', '=', $id_user)
            ->where('comment_state.id_user', '!=', $id_user)
            ;
        return $request->get();
    }

    /**
     * @ghost\Param(name="id_comment",type="int",requirements="\d+",required=true)
     */
    public function delete(Request $request)
    {
        $id_comment = $request->input("id_comment");
        $comment = Comment::find($id_comment);
        if(isset($comment)){
            $comment->delete();
        }
        return $id_comment;
    }
}
