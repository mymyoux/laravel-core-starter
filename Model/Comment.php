<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;
use DB;

class Comment extends Model
{
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    protected $table = 'comment';
    protected $primaryKey = 'id_comment';

    protected $fillable = ['comment','external_id','external_type'];
    public function external()
    {
        return $this->morphTo();
    }
}
