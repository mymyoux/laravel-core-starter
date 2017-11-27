<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;
use Db;
use Auth;
use Illuminate\Database\Eloquent\Builder;
class Template extends \Tables\Model\Template
{

    protected $fillable = ['path','type','locale','md5','version'];

}

