<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

class Comment extends \Tables\Model\Comment
{
    use SoftDeletes;
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    protected $dates = ['deleted_at'];

    protected $table = 'comment';
    protected $primaryKey = 'id_comment';

    protected $fillable = ['comment','id_comment_relation','id_user'];
    public function relation()
    {
        return $this->belongsTo(CommentRelation::class, 'id_comment_relation','id_comment_relation');
    }
     public function user()
    {
        return $this->belongsTo('App\User', 'id_user','id_user');
    }
}
