<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;

class Action extends \Tables\Model\Action
{
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $table = 'actions';
	protected $primaryKey = 'id_action';

}