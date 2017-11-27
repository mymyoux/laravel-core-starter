<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;
use DB;

class CommentRelationUser extends \Tables\Model\Comment\Relation\User
{
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    protected $table = 'comment_relation_user';
    protected $primaryKey = 'id_comment_relation_user';

    protected $fillable = ['id_user','id_comment_relation'];

    public function relation()
    {
        return $this->belongsTo(CommentRelation::class, 'id_comment_relation','id_comment_relation');
    }
    public function external()
    {
        return $this->morphTo();
    }
}
