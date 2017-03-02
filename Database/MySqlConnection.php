<?php

namespace Core\Database;

use Illuminate\Database\MySqlConnection as BaseMySqlConnection;

use PDO;
use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Database\Query\Processors\MySqlProcessor;
use Doctrine\DBAL\Driver\PDOMySql\Driver as DoctrineDriver;
use Core\Database\Query\Grammars\MySqlGrammar as QueryGrammar;
use Illuminate\Database\Schema\Grammars\MySqlGrammar as SchemaGrammar;

class MySqlConnection extends BaseMySqlConnection
{
       public function getSchemaBuilder()
    {
        dd('ok');
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new MySqlBuilder($this);
    }
     /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Schema\Grammars\MySqlGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        dd('what');
        return $this->withTablePrefix(new SchemaGrammar);
    }
}
