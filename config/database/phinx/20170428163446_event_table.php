<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;
class EventTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
   public  $actions = ['delete'=> 'CASCADE', 'update'=> 'CASCADE'];
    public function migrate()
    {
        $this->rollback();
        $this->execute('ALTER TABLE user_role ADD COLUMN id_user_role int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY');

    }
    public function rollback()
    {
        if($this->table('user_role')->hasColumn('id_user_role'))
        {
         $this->execute('ALTER TABLE user_role DROP COLUMN id_user_role');
        } 
    }
    // /!\ during migrate: changing then migrate
    // /!\ during rollback: changing then rollback
    public function changing()
    {
             $this->table('event', ['id' => false, 'primary_key' => ['id_event']])
            ->addColumn('id_event', 'integer', ['limit' => 11, 'null' => false, 'signed' => false,'identity'=>True])
            ->addColumn('external_id', 'integer', ['limit' => 11, 'null' => true, 'signed' => false])
            ->addColumn('external_type', 'string', ['limit' => 256, 'null' => true])
            ->addColumn('type', 'string', ['limit' => 2000, 'null' => false])
            ->addColumn('state', 'string', ['limit' => 2000, 'null' => false])
            ->addColumn('data', 'text', ['limit' => MysqlAdapter::TEXT_LONG, 'null' => true])
            ->addColumn('result', 'text', ['limit' => MysqlAdapter::TEXT_LONG, 'null' => true])
            ->addColumn('owner_id', 'integer', ['limit' => 11, 'null' => true, 'signed' => false])
            ->addColumn('owner_type', 'string', ['limit' => 256, 'null' => true])
            ->addColumn('created_time', 'timestamp', ['null' => true])
            ->addColumn('updated_time', 'timestamp', ['null' => true])
            ->addIndex(['type'])
            ->addIndex(['external_id','external_type'])
            ->addIndex(['owner_id','owner_type'])
            ->create();
    }
    public function dropTable($tablename)
    {
        if($this->hasTable($tablename))
        {
            return parent::dropTable($tablename);
        }
    }
    public function addPrecision($tablename, $column = NULL, $precision = 3)
    {
        //change proof
        if(!$this->hasTable($tablename))
        {
            return;
        }
        if(!isset($column))
        {
            $this->execute("ALTER TABLE `".$tablename."` MODIFY updated_time TIMESTAMP(".$precision.") DEFAULT CURRENT_TIMESTAMP(".$precision.") ON UPDATE CURRENT_TIMESTAMP(".$precision.")");
            $this->execute("ALTER TABLE `".$tablename."` MODIFY created_time TIMESTAMP(".$precision.") DEFAULT CURRENT_TIMESTAMP(".$precision.")");
        }else
        {
             $this->execute("ALTER TABLE `".$tablename."` MODIFY `".$column."` TIMESTAMP(".$precision.") DEFAULT CURRENT_TIMESTAMP(".$precision.")");
        }
    }

     public function addHistory($tablename,$tablename_history = NULL, $columns = NULL, $trigger = NULL, $excludes = NULL)
    {
        if(!isset($tablename_history))
        {
            $tablename_history = $tablename."_history";
        }
        if(!$this->hasTable($tablename) || $this->isRollback())
        {
            //down method 
            if($this->hasTable($tablename_history))
            {
                $this->execute("DROP TRIGGER IF EXISTS after_insert_".$tablename."_".$tablename_history);
                $this->execute("DROP TRIGGER IF EXISTS after_update_".$tablename."_".$tablename_history);
                $this->execute("DROP TABLE IF EXISTS ".$tablename_history);
            }
            return;
        }
        if(!isset( $excludes))
        {
             $excludes = [];
        }
        $excludes[] = "created_time";
        $excludes[] = "updated_time";
        $db_name = $this->adapter->getOptions()["name"];


        $existing_columns_data = $this->fetchAll("SELECT * 
                                FROM `INFORMATION_SCHEMA`.`COLUMNS` 
                                WHERE `TABLE_SCHEMA`= '".$db_name."'
                                AND `TABLE_NAME`='".$tablename."';");

        $existing_columns = array_map(function($item)
            {
                return $item["COLUMN_NAME"];
            }, $existing_columns_data);

        if(!isset($columns))
        {
            $columns = array_values(array_filter($existing_columns, function($item) use($excludes)
                {
                    if(in_array($item, $excludes))
                    {
                        return False;
                    }
                    return True;
                }));
        }
        if(!isset($trigger))
        {
            $trigger = $columns;
        }


        //CREATION TABLE;
        $syntax_origin = $this->fetchRow('SHOW CREATE TABLE '.$tablename);
        $syntax_origin = explode("\n", $syntax_origin["Create Table"]);
        $creation = [];
        $columns_order = [];
        foreach($syntax_origin as $line)
        {
            foreach($columns as $column)
            {
                if(preg_match('/^ *`'.$column.'`/i', $line) !== 0)
                {
                    $line = str_replace('AUTO_INCREMENT', '', rtrim($line, ","));
                    $creation[] = $line;
                    $columns_order[] = $column;
                }
            }
            
        }
        $creation[] = ' `created_time` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)';
        $creation[] = ' `updated_time` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3)';
        $this->execute('CREATE TABLE '.$tablename_history.' ('. implode(",\n", $creation).')');


        $values = array_map(function($item)
            {
                return "NEW.".$item;
            }, $columns_order);

         $ifs = array_map(function($item)
            {
                return "NEW.".$item.' <> OLD.'.$item;
            }, $trigger);

        $values[]= "NOW(3)";
        $values[]= "NOW(3)";

           $this->execute("
        CREATE TRIGGER after_insert_".$tablename."_".$tablename_history."
          AFTER INSERT ON ".$tablename."
          FOR EACH ROW
          BEGIN
          INSERT INTO ".$tablename_history." VALUES(".implode(",",$values).");
          END
         ");


        $this->execute("
        CREATE TRIGGER after_update_".$tablename."_".$tablename_history."
          AFTER UPDATE ON ".$tablename."
          FOR EACH ROW
          BEGIN
              IF ".implode(" OR ", $ifs)." THEN
                 INSERT INTO ".$tablename_history." VALUES(".implode(",",$values).");
              END IF;
          END
         ");
    }
    protected $_isRollback;
    public function isRollback()
    {
        if(!isset($this->_isRollback))
        {
            $this->_isRollback = $this->adapter->getAdapterType() == "ProxyAdapter";
        }
        return $this->_isRollback;
    }
    public function up()
    {
        $this->_isRollback = False;
        $this->migrate();
    }
    public function down()
    {
        $this->_isRollback = True;
        $this->rollback();
    }
    public function change()
    {
        if(method_exists($this, "changing"))
            $this->changing();
        if($this->isRollback())
        {
            if(method_exists($this, "rollback"))
            {
                $this->rollback();
            }
        }else
        {
            if(method_exists($this, "migrate"))
            {
                $this->migrate();
            }
        }
    }
}
