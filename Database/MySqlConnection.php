<?php

namespace Core\Database;

use Illuminate\Database\MySqlConnection as BaseMySqlConnection;

use PDO;
use Illuminate\Database\Schema\MySqlBuilder;
use Core\Database\Query\Processors\MySqlProcessor;
use Doctrine\DBAL\Driver\PDOMySql\Driver as DoctrineDriver;
use Core\Database\Query\Grammars\MySqlGrammar as QueryGrammar;
use Illuminate\Database\Schema\Grammars\MySqlGrammar as SchemaGrammar;

class MySqlConnection extends BaseMySqlConnection
{
    protected function getDefaultPostProcessor()
    {
        return new MySqlProcessor;
    }
}
