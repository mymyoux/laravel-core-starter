<?php

namespace Core\Console\Commands\Cli;
use Core\Console\Commands\CoreCommand;
use Symfony\Component\Console\Input\InputOption;
use App;
use Illuminate\Foundation\Providers\ArtisanServiceProvider;
use DB;
use File;
use Core\Model\Cron;
use Logger;

class GenerateCron extends CoreCommand
{
    protected $current_directory;
    protected $cache;
    protected $cachefilename;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cli:generate-cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate cron in file';

    /**
     *
     * Execute the console command.
     *
     * @return mixed
     */
    public function start()
    {
    	$lines 			= [];
        $config_path 	= config('cron.path');
        $namespaces 	= config('cron.namespaces');

    	foreach ($namespaces as $path => $type)
    	{
    		$namespace 	= '\\' . $type . '\Console\Commands\\';
	        $root  		= base_path();
	        $folder 	= join_paths($root, "$path/Console/Commands");
	        $files 		= File::allFiles($folder);

	        foreach ($files as $file)
	        {
	            $infos = pathinfo($file);
	            if($infos["extension"] != "php")
	                continue;


	            $methodAnnotations = [];
	            $classAnnotations  = [];

	            $current_folder = substr($infos["dirname"], strlen($folder)+1);

	            $current_namespace = $namespace;

	            $prefix_class = str_replace('/','\\', $current_folder);
	            if(strlen($prefix_class))
	            {
	                $prefix_class.='\\';
	            }

	            $class = $current_namespace.$prefix_class.$infos["filename"];
	            $className = $prefix_class.$infos["filename"];
	            $reflectedClass = new \ReflectionClass($current_namespace.$className);


	            if ($reflectedClass->hasProperty('crontab_config'))
	            {
	            	if ($className === 'CoreCommand') continue;

	            	$object = new $class();

        			$cron = Cron::where('name', '=', $object->getSignature())->first(); //$this->modelCron->findByName( $name );

            		if (null === $cron || null === $object->crontab_config) continue;

            		if ($cron->crontab_config != $object->crontab_config)
            		{
            			$cron->crontab_config = $object->crontab_config;
            			$cron->save();
            		}

		            $configs = explode(';', $object->crontab_config);
		            $options = explode(';', $object->crontab_options);

		            foreach ($configs as $key => $config)
		            {
		                if (true === isset($options[$key]) && !empty($options[$key]) && false !== mb_strpos($options[$key], '|timezones|'))
		                {
		                    //
		                    for ($i = -12; $i <= 12; ++$i)
		                    {
		                        $config_timezone = explode(' ', preg_replace('/[ ]+/', ' ', $config));
		                        $config_timezone[1] = (24 - $i + (int)$config_timezone[1]) % 24;

		                        $line = implode("\t", $config_timezone) . "\t";
		                        $line .= $cron['user'] . "\t";

		                        if (null === $cron['cmd'])
		                            $line .= '/usr/bin/php ' . $cron['directory'] . 'console ' . preg_replace('/ /', ' ', $cron['name'], 1);
		                        else
		                            $line .= $cron['cmd'];

		                        if (true === isset($options[$key]) && false === empty($options[$key]))
		                            $line .= ' ' . str_replace('|timezones|', 'time=' . $i, $options[ $key ]);

		                        if ($cron['server_log'] == 1)
		                            $line .= ' >> ' . $cron['directory'] . 'logs/' . preg_replace('/[^A-Za-z0-9]/', '_',  $cron['name']) . '.log';

		                        $lines[] = $line;
		                    }
		                    continue;
		                }
		                $line = preg_replace('/[ ]+/', "\t", $config) . "\t";
		                $line .= $cron['user'] . "\t";

		                if (null === $cron['cmd'])
		                    $line .= '/usr/bin/php ' . $cron['directory'] . 'console ' . preg_replace('/ /', ' ', $cron['name'], 1);
		                else
		                    $line .= $cron['cmd'];

		                if (true === isset($options[$key]) && false === empty($options[$key]))
		                    $line .= ' ' . $options[ $key ];

		                $line .= ' cron=1';

		                if ($cron['server_log'] == 1)
		                    $line .= ' >> ' . $cron['directory'] . 'logs/' . preg_replace('/[^A-Za-z0-9]/', '_',  $cron['name']) . '.log';

		                $lines[] = $line;
		            }
				}
	        }
    	}

    	$data = implode(PHP_EOL, $lines) . PHP_EOL;
        echo $data;

        if (env('APP_ENV') === 'prod')
        {
            $name       = 'yborder_' . str_replace('-', '_', env('APP_ENV'));
            $filename   = $config_path . $name;

            Logger::info('>> generate in ' . $filename);

            if (true === file_exists($filename))
            {
                $old_content = file_get_contents($filename);

                if ($old_content !== $data)
                {
                	if ($this->confirm('Do you want to erase the file ?', 'yes'))
                	{
	                   	Logger::info('>> UPDATE CONTENT');
	                    file_put_contents($filename, $data);
                	}
                }
                else
                {
                    Logger::normal('== no update');
                }

            }
            else
                Logger::error('<< file not exist ' . $filename);
        }
    }
}
