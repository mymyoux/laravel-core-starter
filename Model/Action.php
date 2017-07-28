<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;

class Action extends Model
{
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $table = 'actions';
	protected $primaryKey = 'id_action';

}