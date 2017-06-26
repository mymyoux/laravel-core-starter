<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;
use DB;

class CommentRelation extends Model
{
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    protected $table = 'comment_relation';
    protected $primaryKey = 'id_comment_relation';

    protected $fillable = ['name'];

    public function comment()
    {
        return $this->morphOne('Core\Model\Comment', 'external');
    }
}
