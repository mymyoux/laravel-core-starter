<?php
namespace Core\App;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Contracts\Config\Repository as RepositoryContract;
use Symfony\Component\Finder\Finder;
use Core\Util\ModuleHelper;
class MultiEnvironmentLoadConfiguration extends LoadConfiguration
{


    protected function loadConfigurationFiles(Application $app, RepositoryContract $repository)
    {
    	$env = env('APP_ENV', "").".";

    	$files = $this->getConfigurationFiles($app);

    	$data = [];

        foreach ($files as $key => $path) {
        	if(substr($key, 0, strlen($env)) == $env)
            {
            	 $key = substr($key, strlen($env));
            	 if(isset($data[$key]))
            	 {
            	 	$data[$key] = $this->configurationMerge($data[$key], require $path);
            	 	continue;
            	 }
            }
            $data[$key] = require $path;
        }
        foreach($data as $key => $value)
        {
        	$repository->set($key, $value);	
        }
    }
    protected function configurationMerge($config1, $config2)
    {
    	foreach($config2 as $key=>$value)
    	{
    		if(substr($key, 0, 1) == "+")
    		{
    			$key = substr($key, 1);
    			if(!isset($config1[$key]))
    			{
    				throw new \Exception('configuration '.$key.' has modifier but no parent');
    			}
    			foreach($value as $k=>$v)
    			{
    				if(is_numeric($k))
    				{
    					$config1[$key][] = $v;
    				}else
    				{
    					$kclean = $k;
    					if(substr($kclean, 0, 1) == "+")
    					{
    						$kclean = substr($kclean, 1);
    					}
    					if(is_array($v) && is_array($config1[$key][$kclean]))
    					{
    						
    						$config1[$key] = $this->configurationMerge($config1[$key], $value);
    					}else
    					{
    						$config1[$key][$k] = $v;
    					}
    				}
    			}
    			unset($config2["+".$key]);
    		}else
    		if(substr($key, 0, 1) == "-")
    		{
    			$key = substr($key, 1);
    			if(!isset($config1[$key]))
    			{
    				throw new \Exception('configuration '.$key.' has modifier but no parent');
    			}
    			foreach($value as $k=>$v)
    			{
    				if(is_numeric($k))
    				{
    					$found = array_search($v, $config1[$key]);
    					if($found === False)
    					{
    						throw new \Exception('try to remove '.$v.' from parent configuration on key '.$key.' but it doesn\'t exist');
    					}
    					array_splice($config1[$key], $found, 1);
    					$config1[$key] = array_values($config1[$key]);
    				}else
    				{
    					$config1[$key][$k] = $v;
    				}
    			}
    			unset($config2["-".$key]);
    		}
    	}
    	return array_merge($config1, $config2);
    }
	protected function getConfigurationFiles(Application $app)
    {
        $files = [];

        $configPath = realpath($app->configPath());

		$paths = 
		array_filter(array_map(function($module)
		{
			return base_path(join_paths($module["path"], "config"));
		}, ModuleHelper::getModulesFromComposer()),
		function($path)
		{	
			return file_exists($path);
		});
		$paths = array_reverse($paths);
		
		$paths[] = $configPath;
        $env = env('APP_ENV', "").".";

        $second = [];
		foreach($paths as $path)
		{
			foreach(Finder::create()->files()->name('*.php')->in($path) as $file) {
				$directory = $this->getNestedDirectory($file, $configPath);
				if(strlen($directory))
				{
					if(substr($directory, 0, strlen($env)) == $env)
					{
						$second[$directory.basename($file->getRealPath(), '.php')] = $file->getRealPath();
					}
					continue;
				}
				$files[$directory.basename($file->getRealPath(), '.php')] = $file->getRealPath();
			}

		}
        return $files + $second;
    }

}
