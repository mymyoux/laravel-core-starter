<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;
use DB;

class CommentRelation extends \Tables\Model\Comment\Relation
{
	const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $table = 'comment_relation';
    protected $primaryKey = 'id_comment_relation';

    protected $fillable = ['name'];

    public function comment()
    {
        return $this->morphOne('Core\Model\Comment', 'external');
    }
     
    public function objects()
    {
        return $this->hasMany('Core\Model\CommentRelationUser', 'id_comment_relation','id_comment_relation');
    }
}
