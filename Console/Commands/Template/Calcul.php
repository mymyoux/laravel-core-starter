<?php

namespace Core\Console\Commands\Template;
use Db;
use Illuminate\Console\Command;
use Core\Util\ClassWriter;
use Schema;
use App;
use Api;
use App\User;
use Tables\TEMPLATE;
use Logger;
class Calcul extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'template:calcul';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcul templates';

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
       $templates = API::get('view/get-all')->send();
       $types = array_merge(["app", "core"], User::getAvailableTypes());
       foreach($types as $type)
       {
           foreach($templates as $template)
           {
               $result = API::get('view/get')->send(['type'=>$type, "path"=>$template, "skiphelpers"=>True])["template"];
                $rawTemplate = 
                [
                    "path"=>$template,
                    "type"=>$type,
                    "md5"=>md5($result)
                ];
                $dbTemplate = TEMPLATE::select('id_template','md5')->where(["path"=>$template,"type"=>$type])->first();
                if(!isset($dbTemplate))
                {
                    Logger::info('create '.$template.' ['.$type.']');
                    $rawTemplate["version"] = 1; 
                    $id = TEMPLATE::insertGetId($rawTemplate);
                    continue;
                }else
                {
                    $id = $dbTemplate->id_template;
                    if($dbTemplate->md5 == $rawTemplate["md5"])
                    {
                        continue;
                    }
                }
                Logger::warn('update '.$template.' ['.$type.']');
                TEMPLATE::where(["path"=>$template,"type"=>$type])->update(['md5'=>$rawTemplate["md5"]]);
                TEMPLATE::select('id_template','md5')->where(["path"=>$template,"type"=>$type])->increment('version');
           }
       }
    }
}
