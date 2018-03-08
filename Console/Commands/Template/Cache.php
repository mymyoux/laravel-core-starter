<?php

namespace Core\Console\Commands\Template;
use DB;
use Illuminate\Console\Command;
use Core\Util\ClassWriter;
use Schema;
use App;
use Api;
use App\User;
use Core\Model\Template;
use Logger;
use Core\Model\Translation;
class Cache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'template:cache {--increment-all=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache templates';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    protected function memory()
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        $size = memory_get_usage(true);
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $locales = Translation::getLocales();
       $templates = API::get('vue/get-all')->send();
       $types = array_merge(["app", "core"], User::getAvailableTypes());
       foreach($locales as $locale)
       {
        foreach($types as $type)
        {
            
            foreach($templates as $template)
            {
                $result = API::get('vue/get')->send(['locale'=>$locale,'type'=>$type, "path"=>$template, "skiphelpers"=>True])["template"];
                    $rawTemplate = 
                    [
                        "path"=>$template,
                        "type"=>$type,
                        "md5"=>md5($result),
                        "locale"=>$locale
                    ];
                    $dbTemplate = Template::select('id_template','md5')->where(["locale"=>$locale,"path"=>$template,"type"=>$type])->first();
                    if(!isset($dbTemplate))
                    {
                        Logger::info('create '.$template.':'.$locale.' ['.$type.']');
                        $rawTemplate["version"] = 1; 
                        $id = Template::insertGetId($rawTemplate);
                        $this->cache($template, $type, $locale);
                        continue;
                    }else
                    {
                        $id = $dbTemplate->id_template;
                        if($dbTemplate->md5 == $rawTemplate["md5"])
                        {
                            continue;
                        }
                    }
                    Logger::warn('update '.$template.':'.$locale.' ['.$type.']');
                    Template::where(["id_template"=>$id])->update(['md5'=>$rawTemplate["md5"]]);
                    Template::find($id)->increment('version');
                    $this->cache($template, $type, $locale);
            }
        }
       }
       $increment_all = $this->option('increment-all');
       if($increment_all != 0)
       {
           Logger::warn('increment all version by 1');
            Template::update(["version"=>DB::raw('version + 1')]);
       }
    }
    private function cache($name, $type, $locale)
    {
        $filepath = storage_path('framework/cache/views/'.$locale.'/'.$type.'/'.$name.'.php');
        $dirpath = dirname($filepath);
        if(!file_exists($dirpath))
        {
            mkdir($dirpath, 0777, True);
        }
        if(file_exists($filepath))
            unlink($filepath);
        $result = API::get('vue/get')->send(['locale'=>$locale,'type'=>$type, "path"=>$name, "skiphelpers"=>False]);
        Logger::info($filepath." ==> ".$result["version"]);
        file_put_contents($filepath, "<?php\nreturn ".var_export($result, True).";");
    }

}
