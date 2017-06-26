<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;
use DB;

class CommentRelationUser extends Model
{
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    protected $table = 'comment_relation_user';
    protected $primaryKey = 'id_comment_relation_user';

    protected $fillable = ['id_user','id_comment_relation'];

    public function relation()
    {
        return $this->belongsTo('Core\Model\CommentRelation', 'id_comment_relation','id_comment_relation');
    }
    public function user()
    {
        return $this->belongsTo('App\User', 'id_user','id_user');
    }
}
