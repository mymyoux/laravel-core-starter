<?php

namespace Core\Database\Query\Grammars;

use Illuminate\Support\Str;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JsonExpression;

class MySqlGrammar extends \Illuminate\Database\Query\Grammars\MySqlGrammar
{
    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat()
    {
        //dd('h');
        return 'Y-m-d H:i:s.s';
    }
}
