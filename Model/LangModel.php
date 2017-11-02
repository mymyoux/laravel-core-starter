<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;
use DB;

class LangModel extends \Tables\Model\Lang
{
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    protected $table = 'lang';
    protected $primaryKey = 'id_lang';

    // protected $fillable = ['title', 'description', 'source', 'external_id', 'external_type'];
}
