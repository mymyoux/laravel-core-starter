<?php

namespace Core\Jobs;
use Core\Queue\JobHandler;
use Logger;
use Artisan;
use Core\Util\Command;
use Illuminate\Console\Application;
class Translation extends JobHandler
{
    
	public function handle()
    {
        try
        {
            Artisan::call('template:cache');
            $path = config('api.yborder.path');
            if(isset($path))
            {

                chdir(config('api.yborder.path'));
                $result = Command::execute(Application::phpBinary(), ["console", "cli:calcul-templates"],True, True);
            }

        }catch(\Exception $e)
        {
            dd($e);
        }
    }
}
