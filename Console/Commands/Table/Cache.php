<?php

namespace Core\Console\Commands\Table;
use Db;
use Illuminate\Console\Command;
use Core\Util\ClassWriter;
use Core\Util\ClassWriter\Body\Table;
use Core\Util\ClassWriter\Body\General;
use Schema;
use ReflectionClass;
use File;
class Cache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'table:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $start = microtime(True);

        $files = [];

       // $this->call('table:clear');

        $folder = base_path('bootstrap/tables');
        if(!file_exists($folder))
        {
            mkdir($folder, 0777);
        }

        $previous = array_map(function($item) use($folder)
            {
                return substr($item, strlen($folder)+1);
            }, File::files($folder));

        $platform = Schema::getConnection()->getDoctrineSchemaManager()->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('enum', 'string');

        //build new

        $cls = new ClassWriter();

        $cls->setNamespace('Tables');
        $cls->setClassName('Table');

        $tables = array_map('reset', DB::select('SHOW TABLES'));
        $list = [];
        foreach($tables as $tablename)
        {
            //$cls->addProperty($tablename, "public", True);
            $cls->addConstant(strtoupper($tablename), $tablename);
            $list[] = $tablename;
        }
        $cls->addProperty("_tables", "protected", True, $list);

        $model = new ReflectionClass(General::class);
        foreach($model->getMethods() as $method)
        {
            $cls->addMethod(General::class, $method->name);
        }

        $files[] = "Table.php";
        $cls->write(join_paths($folder, "Table.php"));
        $this->updateOwnerShip(join_paths($folder, "Table.php"));

        $this->info('Table.php generated');

        $model = new ReflectionClass(Table::class);

        foreach($tables as $tablename)
        {
            $cls = new ClassWriter();
            $cls->setNamespace('Tables');
            $cls->addUse('Db');
            $cls->setClassName(strtoupper($tablename));
            $cls->addConstant("TABLE", $tablename);

            $columns = Schema::getColumnListing($tablename);

            foreach($columns as $column)
            {
                $cls->addConstant(strtolower($column), $tablename.".".$column);
            }
            $datacolumns = [];
            foreach($columns as $column)
            {
                $cls->addConstant("s_".strtolower($column), $column);
                //$this->info($tablename.".".$column);
                $datacolumns[$column] = Schema::getColumnType($tablename, $column);
            }
            foreach($model->getMethods() as $method)
            {
                $cls->addMethod(Table::class, $method->name);
            }

            $cls->addProperty("_columns", "protected", True, $datacolumns);
            $files[] = strtoupper($tablename).".php";
            $export = $cls->export();
            $path = join_paths($folder, strtoupper($tablename).".php");
            if(!file_exists($path) || md5($export) != md5_file($path))
            {
                $cls->write($path);
                $this->updateOwnerShip($path);
                $this->info(strtoupper($tablename).'.php generated');
            }
        }
        $end = microtime(True);

        $to_remove = array_diff($previous, $files);
        foreach($to_remove as $remove)
        {
            unlink(join_paths($folder, $remove));
               $this->info($remove.' deleted');
        }
    }
    protected function updateOwnerShip($path)
    {
        if(config('update.user'))
        {
            chown((string)$path, config('update.user'));
        }
        if(config('update.group'))
        {
            chgrp((string)$path, config('update.group'));
        }

    }
}
