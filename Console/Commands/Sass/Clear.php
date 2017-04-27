<?php
namespace Core\Console\Commands\Sass;
use Illuminate\Console\Command as BaseCommand;
use Core\Util\Command;
use App;
use Logger;
class Clear extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sass:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear minified sass files';

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
       rrmdir(public_path('css/min'));
       rrmdir(public_path('css/map'));
        $cachepath = storage_path('framework/cache/assets.php');
        if(!file_exists($cachepath))
        {
            return;
        }
        $cache = include $cachepath;
        $keys = array_keys($cache);
        $csskeys = array_values(array_filter($keys, function($item)
        {
            return ends_with($item, ".css");
        }));
        foreach($csskeys as $key)
       {
           unset($cache[$key]);
       } 
       if(empty($cache))
       {
           Logger::info("remove $cachepath");
           unlink($cachepath);
       }
       file_put_contents($cachepath, "<?php\nreturn ".var_export($cache, True).";");
    }
}
