<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;
use Db;
use Auth;
use Illuminate\Database\Eloquent\Builder;
class Template extends Model
{

	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';


    protected $table = 'template';
    protected $primaryKey = 'id_template';

    protected $fillable = ['path','type','locale','md5','version'];


}

