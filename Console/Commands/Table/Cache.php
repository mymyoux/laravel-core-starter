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
use Core\Util\ModuleHelper;
use Core\Util\ClassHelper;
use Logger;
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

        //TODO:change default extends for a table
        //TODO:ignore some tables (piwik)
        //TODO:update gitconfig
        //TODO:remove old tables

        $reserved = ['__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'parent', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor'];

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

       

        $extendsMapping = config("database.model.mapping")??[];
        //relations
        $database = config('database.connections.mysql.database');
        Db::statement("SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO'");
        $result = Db::select("SELECT INFORMATION_SCHEMA.KEY_COLUMN_USAGE.TABLE_NAME, GROUP_CONCAT(INFORMATION_SCHEMA.KEY_COLUMN_USAGE.COLUMN_NAME) as `columns`, INFORMATION_SCHEMA.KEY_COLUMN_USAGE.CONSTRAINT_NAME, INFORMATION_SCHEMA.KEY_COLUMN_USAGE.REFERENCED_TABLE_NAME, GROUP_CONCAT(INFORMATION_SCHEMA.KEY_COLUMN_USAGE.REFERENCED_COLUMN_NAME) as referenced_columns, INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS.UPDATE_RULE, INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS.DELETE_RULE FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS ON INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS.CONSTRAINT_SCHEMA = INFORMATION_SCHEMA.KEY_COLUMN_USAGE.REFERENCED_TABLE_SCHEMA AND  INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS.CONSTRAINT_NAME = INFORMATION_SCHEMA.KEY_COLUMN_USAGE.CONSTRAINT_NAME WHERE INFORMATION_SCHEMA.KEY_COLUMN_USAGE.REFERENCED_TABLE_SCHEMA = '".$database."' GROUP BY INFORMATION_SCHEMA.KEY_COLUMN_USAGE.CONSTRAINT_NAME;");
        Db::statement("SET SQL_MODE=@OLD_SQL_MODE");
        
        //get modules
        $modules = array_map(function($item)
        {
            $item["path"] = base_path($item["path"]);
            $item = std($item);
            $files = File::allfiles($item->path);
            //TODO: search all already models into exitings class => change their extends to generated
            //TODO: generate others class into App\Model that's just extends 
            // $cls = 
            // dd($files);
            return $item;
        }
        , ModuleHelper::getModulesFromComposer());

        
        $core_module = NULL;
        foreach($modules as $module)
        {
            if($module->module == "Core\\")
            {
                $core_module = $module;
                break;
            }
        }
        //app module
        $module = $modules[0];

        $core_extends = [];

        if(isset($core_module))
        {
            $files = $this->getTableFiles($core_module, [join_paths($core_module->path,"config")]);
            foreach($files as $file)
            {
                if(isset($core_extends[$file->table]))
                    continue;
                $core_extends[$file->table] = "\\".$file->fullname;
            }
        }
        $relations = array_reduce($result, function($previous, $item)
        {
            if(!isset($previous["relations"][$item->TABLE_NAME]))
            {   
                $previous["relations"][$item->TABLE_NAME] = [];
            }
            $previous["relations"][$item->TABLE_NAME][] = $item;

            if(!isset($previous["inverse"][$item->REFERENCED_TABLE_NAME]))
            {   
                $previous["inverse"][$item->REFERENCED_TABLE_NAME] = [];
            }
            $previous["inverse"][$item->REFERENCED_TABLE_NAME][] = $item;


            return $previous;
        }, ["relations"=>[], "inverse"=>[]]);

        //get structure
        $structure = Db::select("SELECT INFORMATION_SCHEMA.COLUMNS.* FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='".$database."'  ORDER BY TABLE_NAME ASC, INFORMATION_SCHEMA.COLUMNS.ORDINAL_POSITION ASC");
        $structure = array_reduce($structure, function($previous, $item)
        {   
            if(!isset($previous[$item->TABLE_NAME]))
            {
                $previous[$item->TABLE_NAME] = ["columns"=>[],"primaries"=>[],"uniques"=>[]];
            }
            $previous[$item->TABLE_NAME]["columns"][] = &$item;
            if($item->COLUMN_KEY == "PRI")
            {
                $previous[$item->TABLE_NAME]["primaries"][] = &$item;
            }elseif($item->COLUMN_KEY == 'UNI')
            {
                $previous[$item->TABLE_NAME]["uniques"][$item->COLUMN_NAME] = &$item;
            }
            return $previous;
        }, []);


        $tables = array_map('reset', DB::select('SHOW TABLES'));

        $models_folder = base_path('bootstrap/tables/Model');
        
        //File::deleteDirectory($models_folder, True);
        
        
        if(!file_exists($models_folder))
        {
            mkdir($models_folder, 0777);
        }
        $extends = config('database.model.default')??'\Core\Database\Eloquent\Model';
        $cast = [];
        
        $cast_mapping = ["int"=>"integer","varchar"=>"string","lontext"=>"string","timestamp"=>"datetime","text"=>"string","datetime"=>"datetime","float"=>"float","tinytext"=>"text","bigint"=>"integer","tinyint"=>"integer","date"=>"date","smallint"=>"integer"];

        $models_cls = [];
        foreach($tables as $table)
        {
            $cls = new ClassWriter();

            $mf = $models_folder;
            $file = $table;
            $namespace = 'Tables\Model';
            if(strpos($table,  "_") !== False)
            {
                $list = explode("_", $table);
                for($i=0; $i<count($list)-1; $i++)
                {
                    $namespace.= '\\'.ucfirst($list[$i]);
                    $mf = join_paths($mf, ucfirst($list[$i]));
                    if(!file_exists($mf))
                    {
                        mkdir($mf, 0777);
                    }
                }
                $file = last($list);
            }
            $file = ucfirst($file);
            if(ends_with($file, "s"))
            {
                $file = substr($file, 0,-1);
            }
            if(in_array(strtolower($file), $reserved))
            {
                $file.="Model";
            }
            $cls->setNamespace($namespace);
            $cls->setClassName($file);
            if(isset($extendsMapping[$table]))
            {
                $cls->setExtends($extendsMapping[$table]);
            }else
            {
                $cls->setExtends($extends);
            }
            
            //default table name
            if($table != strtolower($file)."s")
                $cls->addProperty('table', 'protected', False, $table);

            
            if(isset($structure[$table]))
            {
                $created_at = NULL;
                $updated_at = NULL;
                $deleted_at = NULL;
                
                if(count($structure[$table]["primaries"])==1)
                {
                    //primary key
                    $primary = first($structure[$table]["primaries"]);
                    //default id name
                    if($primary->COLUMN_NAME != "id")
                    {
                        $cls->addProperty('primaryKey', 'protected', False, $primary->COLUMN_NAME);
                    }  
                    //auto increment
                    if($primary->EXTRA != "auto_increment")
                    {
                        $cls->addProperty('incrementing', 'public', False, False);
                    }          
                    //not int      
                    if(strpos($primary->DATA_TYPE, "int")===False)
                    {
                        if(isset($cast_mapping[$primary->DATA_TYPE]))
                        {
                            $cls->addProperty('keyType', 'protected', False, $cast_mapping[$primary->DATA_TYPE]);
                        }else
                        {
                            $cls->addProperty('keyType', 'protected', False, "string");
                        }
                    }
                }elseif(count($structure[$table]["primaries"]))
                {
                    $cls->addUse('Core\Model\Traits\HasCompositePrimaryKey');
                    $cls->addUseTrait('HasCompositePrimaryKey');
                    $cls->addProperty('primaryKey','protected', False, array_map(function($item)
                    {
                        return $item->COLUMN_NAME;
                    },$structure[$table]["primaries"]));
                }
                $cast = [];
                foreach($structure[$table]["columns"] as $column)
                {
                    if(strpos($column->COLUMN_NAME,"created_")===0)
                    {
                        if($created_at != "created_at")
                        {
                            $created_at = $column->COLUMN_NAME;
                        }
                    }
                    if(strpos($column->COLUMN_NAME,"updated_")===0)
                    {
                        if($updated_at != "updated_at")
                        {
                            $updated_at = $column->COLUMN_NAME;
                        }
                    }
                    if($column->COLUMN_NAME == "deleted_at")
                    {
                        $deleted_at = "deleted_at";
                    }
                    if(isset($cast_mapping[$column->DATA_TYPE]))
                    {
                        if(isset($column->COLUMN_COMMENT) && strpos($column->COLUMN_COMMENT, "bool")!==False)
                        {
                            $cast[$column->COLUMN_NAME] = "boolean";
                        }else
                        {
                            $cast[$column->COLUMN_NAME] = $cast_mapping[$column->DATA_TYPE];
                        }
                    }
                }
                if(isset($created_at) && $created_at != "created_at")
                {
                    $cls->addConstant('CREATED_AT', $created_at);
                }
                if(isset($updated_at) && $updated_at != "updated_at")
                {
                    $cls->addConstant('UPDATED_AT', $updated_at);
                }
                if(!isset($created_at) && !isset($updated_at))
                {
                    //public ? 
                    $cls->addProperty('timestamps','public', NULL, false);
                }
                if(isset($deleted_at))
                {
                    $cls->addUse('Illuminate\Database\Eloquent\SoftDeletes');
                    $cls->addUseTrait('SoftDeletes');
                    $cls->addProperty('dates','protected', NULL, [$deleted_at]);
                }
                if(!empty($cast))
                {
                    $cls->addProperty('casts', 'protected', False, $cast);
                }
            }

            /*protected $table = TUSER::TABLE;
            protected $primaryKey = 'id_user';*/


            $models_cls[$table] = std(["cls"=>$cls, "table_name"=>$table,"path"=>join_paths($mf, $file.".php"),"name"=>strtolower($file), "fullname"=>$namespace.'\\'.$file]);
        }

        //relations
        foreach($tables as $table)
        {
            $current = &$models_cls[$table];
            $cls = $current->cls;
            if(isset($relations["relations"][$table]))
            {
                $rels = $relations["relations"][$table];
                foreach($rels as $relation)
                {

                    //one col
                    if(strpos($relation->columns, ",")===False)
                    {
                        $related = &$models_cls[$relation->REFERENCED_TABLE_NAME];
                        $content = "";
                        $foreign = False;
                        if(True || $relation->columns != $related->name.'_id')
                        {
                            $content .= ", '".$relation->columns."'";
                            $foreign = True;
                        }
                        if(True || $relation->referenced_columns != 'id')
                        {
                            if(!$foreign)
                            {
                                $content.=", NULL";
                            }
                            $content .= ", '".$relation->referenced_columns."'";
                        }
                        if(!isset($current->relations))
                        {
                            $current->relations = [];
                        }
                        if(!isset($related->relations))
                        {
                            $related->relations = [];
                        }
                        $name = $related->table_name;
                        if(ends_with($name, "s"))
                        {
                            $name = substr($name, 0, strlen($name)-1);
                        }
                        $current->relations[] = std(
                        [
                            "model"=>&$related,
                            "name"=>$name,
                            "content"=>$content,
                            "foreign"=>$relation->columns,
                            "local"=>$relation->referenced_columns,
                            "type"=>"belongsTo"
                        ]);

                        $name = $current->table_name;
                        if($table == "marketplace_search_candidate" && count($current->relations)==2)
                        {
                            ///dd($current->relations);
                        }
                        $type = "hasMany";
                        // if(isset($structure[$relation->REFERENCED_TABLE_NAME]["uniques"][$relation->referenced_columns]))
                        // {
                        //     $type = "hasOne";
                        // }
                        if(isset($structure[$relation->TABLE_NAME]["uniques"][$relation->columns]))
                        {
                            $type = "hasOne";
                        }
                        if($type == "hasMany")
                        {
                            if(!ends_with($name, "s"))
                            {
                                $name .="s";
                            }
                        }
                        $related->relations[] = std( 
                        [
                            "model"=>&$current,
                            "name"=>$name,
                            "content"=>$content,
                            "foreign"=>$relation->columns,
                            "local"=>$relation->referenced_columns,
                            "type"=>$type
                        ]);
                       // $cls->addFunction($related->name, NULL, 'return $this->belongsTo('."'".$related->fullname."'" .$content.");", "public");
                        
                        //TODO:check same method name
                        //TODO: check one/many 
                        //TODO: use extended class instead for link
                       // $related->cls->addFunction($current->name, NULL, 'return $this->hasOne('."'".$current->fullname."'" .$content.");", "public");
                    }
                }
            }
        }
        foreach($tables as $table)
        {
            $current = &$models_cls[$table];
            $cls = $current;
            if(isset($cls->relations))
            {
                $duplicates = array_reduce($cls->relations, function($previous, $item)
                {
                    if(in_array($item->name, $previous->exists))
                    {
                        if(!in_array($item->name, $previous->duplicates))
                            $previous->duplicates[] = $item->name;
                    }else
                    {
                        $previous->exists[] = $item->name;
                    }
                    return $previous;
                }, std(["duplicates"=>[], "exists"=>[]]));
               /* if(!empty($duplicates->duplicates))
                {*/
                    $cls->relations = array_map(function($item) use($duplicates, $current)
                    {
                        if(starts_with($item->name, $current->table_name."_"))
                        {
                            $name = substr($item->name, strlen($current->table_name)+1);
                            if(!in_array($name, $duplicates->exists))
                            {
                                $item->name = $name;
                                $duplicates->exists[] = $name;
                            }
                        }
                        if(!in_array($item->name, $duplicates->duplicates))
                        {
                            return $item;
                        }
                        if($item->foreign == $item->local)
                        {
                            return $item;
                        }
                        if($item->type != "belongsTo")
                        {
                            if(starts_with($item->foreign, "id_"))
                            {
                                $item->name = $item->name."_as_".substr($item->foreign, 3);
                                
                            }
                            if(ends_with($item->foreign, "_id"))
                            {
                                $item->name = $item->name."_as_".substr($item->foreign, 0, strlen($item->foreign)-3);
                            }
                        }else
                        {
                            if(starts_with($item->foreign, "id_"))
                            {
                                $item->name = substr($item->foreign, 3);
                                
                            }
                            if(ends_with($item->foreign, "_id"))
                            {
                                $item->name = substr($item->foreign, 0, strlen($item->foreign)-3);
                            }
                            if(in_array($item->name, $duplicates->duplicates))
                            {
                                $item->name = $item->foreign;
                            }
                        }
                        // if($item->type == "hasMany")
                        // {
                        //     if(!ends_with($item->name, "s"))
                        //     {
                        //         $item->name .= "s";
                        //     }
                        // }
                        return $item;
                    }, $cls->relations);
                // }
                foreach($cls->relations as $relation)
                {
                 //   dd($relation);
                    $current->cls->addFunction($relation->name, NULL, 'return $this->'.$relation->type.'('."'".preg_replace("/^Tables\\\\/",$module->module,$relation->model->fullname)."'".$relation->content.');','public');
                }
            }
        }
        //write classes
        foreach($tables as $table)
        {
            $current = &$models_cls[$table];
            $cls = $current->cls;
            Logger::info("write:\t".$current->path);
            $cls->write($current->path);

        }
        //dd('what');

        $files = $this->getTableFiles([$core_module, $module], [join_paths($core_module->path,"config")]); 
        

        /*File::allfiles($module->path);
        $files = array_values(array_filter(array_map(function($item)
        {
            $cls = ClassHelper::getInformations($item->getRealPath());
            try
            {
                return std(["cls"=>new ReflectionClass($cls->fullname), "path"=>$item->getRealPath(),"file"=>$item,"fullname"=>$cls->fullname]);
            }catch(\Exception $e)
            {
                Logger::error($e);
            }
        },array_values(array_filter($files, function($item)
        {
            return $item->getExtension() == "php";
        }))), function($item)
        {
            return $item->cls->hasProperty('table');  
        }));
        */
        $already_written = array_map(function($item)
        {
            return $item->table;//cls->getDefaultProperties()["table"];
            //return $item->getProperty('table')->getValue();
        }, $files);

        $extendsClasses = array_reduce($files, function($previous, $item)
        {
            //$item->parent = $item->cls->getParentClass()->getName();
            if(!isset($previous[$item->cls->getDefaultProperties()["table"]]))
                $previous[$item->cls->getDefaultProperties()["table"]] = $item;
            return $previous;
            //return $item->getProperty('table')->getValue();
        }, []);

        $extends = ["\Illuminate\Database\Eloquent\Model", "\Core\Database\Eloquent\Model", config('database.model.default')??'\Core\Database\Eloquent\Model'];
        foreach($tables as $table)
        {
            $current = &$models_cls[$table];
            $cls = $current->cls;
            //Logger::info("write:\t".$current->path);
           // $cls->write($current->path);

            if(in_array($table, $already_written))
            {
                if(!isset($extendsClasses[$table]))
                {
                    Logger::error("not exists:\t". $table);
                    continue;
                }
                if($extendsClasses[$table]->parent == $cls->getFullName() || $extendsClasses[$table]->parent == '\\'.$cls->getFullName() )
                {
                   // Logger::error("already changed:\t". $table);
                    continue;
                }
                if(!in_array($extendsClasses[$table]->parent, $extends) && !in_array('\\'.$extendsClasses[$table]->parent, $extends))
                {
                    Logger::warn($extendsClasses[$table]->fullname." use custom extends \t".$extendsClasses[$table]->parent);
                    continue;
                }
             
              
                $content = preg_replace("/extends( |\t)+([a-z0-9_-]+)/i",'extends \\'.$cls->getFullName(), $extendsClasses[$table]->file->getContents());
                Logger::warn("Change\t".$extendsClasses[$table]->fullname." inherits from\t".$extendsClasses[$table]->parent." to\t".$cls->getFullName());
                file_put_contents($extendsClasses[$table]->path, $content);
                // dd($extendsClasses[$table]->file->getContents());
                // $newInherits = config('database.model.default')??'\Core\Database\Eloquent\Model';
                // if($extendsClasses[$table]->parent!= $newInherits && '\\'.$extendsClasses[$table]->parent!= $newInherits)
                 //   Logger::info("Change:\t".$table);
                continue;
            }
            $cls_app = new ClassWriter(); 
            $name = preg_replace("/^Tables\\\\/",$module->module,$cls->getNamespace());
            $cls_app->setNamespace($name);
            $cls_app->setClassName($cls->getClassName());
            $cls_app->setExtends('\\'.$cls->getFullName());
            $folder = $cls_app->getFullName();
            $folder= explode('\\', $folder);
            $folder = join('\\',array_slice($folder, 1));
            $file = str_replace('\\','/',$folder.".php");
            $file = join_paths($module->path, $file); 
            if(!file_exists($file))
            {
                $folder = dirname($file);
                if(!file_exists($folder))
                {
                    File::makeDirectory($folder, 0755, true);
                }
                Logger::info("Create:\t".$file);
                //File::makeDirectory($cache_path . '/' . $directory, 0755, true);
               $cls_app->write($file);
            }
            // $cls_app->
        }

        $existing = [];
        //File::allFiles()
        return;
        dd($models_cls);
        dd('ok');
        //dd($tables);



         //build new

         $cls = new ClassWriter();
         
        $cls->setNamespace('Tables');
        $cls->setClassName('Table');
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
    protected function getTableFiles($module, $exclude = [])
    {
        if(is_array($module))
        {
            return array_merge(...array_map(function($item) use($exclude)
            {
                return $this->getTableFiles($item, $exclude);
            }, $module));
        }
        $files = File::allfiles($module->path);
        $files = array_values(array_filter($files, function($item) use($exclude)
        {
            foreach($exclude as $path)
            {
                if(starts_with( $item->getRealPath(), $path))
                {
                    return False;
                }
            }
            return True;
        }));
        $files = array_values(array_filter(array_map(function($item)
        {
            $cls = ClassHelper::getInformations($item->getRealPath());
            try
            {
                return std(["cls"=>new ReflectionClass($cls->fullname), "path"=>$item->getRealPath(),"file"=>$item,"fullname"=>$cls->fullname]);
            }catch(\Exception $e)
            {
                Logger::error("Ignore:\t" .$item->getRealPath());
                return NULL;
                
            }
        },array_values(array_filter($files, function($item)
        {
            if(!isset($item))
                return NULL;
            return $item->getExtension() == "php";
        }))), function($item)
        {
            if(!isset($item))
                return False;
            if(!$item->cls->hasProperty('table')){
                return False;
            }
            if($item->cls->getParentClass() == False)
                return False;

            $default = $item->cls->getDefaultProperties();            
            if(!isset($default) || !isset($default["table"]))
            {
                return False;
            }
            return True;
        }));

        return array_map(function($item)
        {
            $item->table = $item->cls->getDefaultProperties()["table"];
            $item->parent = $item->cls->getParentClass()->getName();
            return $item;
        }, $files);
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
