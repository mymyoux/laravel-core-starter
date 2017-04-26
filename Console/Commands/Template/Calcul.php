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
    protected $signature = 'template:calcul {--increment-all=0}';

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
        
       $templates = API::get('vue/get-all')->send();
       $types = array_merge(["app", "core"], User::getAvailableTypes());
       foreach($types as $type)
       {
           foreach($templates as $template)
           {
               $result = API::get('vue/get')->send(['type'=>$type, "path"=>$template, "skiphelpers"=>True])["template"];
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
                    $this->cache($template, $type);
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

                $this->cache($template, $type);
           }
       }
       $increment_all = $this->option('increment-all');
       if($increment_all != 0)
       {
           Logger::warn('increment all version by 1');
            TEMPLATE::update(["version"=>Db::raw('version + 1')]);
       }
    }
    private function cache($name, $type)
    {
        $filepath = storage_path('framework/cache/views/'.$name.'.php');
        $dirpath = dirname($filepath);
        if(!file_exists($dirpath))
        {
            mkdir($dirpath, 0777, True);
        }
        $result = API::get('vue/get')->send(['type'=>$type, "path"=>$name, "skiphelpers"=>False]);
        file_put_contents($filepath, "<?php\nreturn ".var_export($result, True).";");
    }

}
