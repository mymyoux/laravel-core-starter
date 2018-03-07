<?php

namespace Core\Model\User\Token;

use Core\Database\Eloquent\Model;
use DB;

class History extends \Tables\Model\User\One\Token\History
{
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';
    
    protected $table = 'user_one_token_history';

    static public function insert($id_user, $token, $source)
    {
        $history = new History;

        $history->id_user = $id_user;
        $history->token = $token;
        $history->source = $source;

        $history->save();

        return $history;
    }
}
