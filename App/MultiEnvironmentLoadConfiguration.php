<?php
namespace Core\App;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Contracts\Config\Repository as RepositoryContract;
use Symfony\Component\Finder\Finder;
use Core\Util\ModuleHelper;
use Illuminate\Support\Arr;
class MultiEnvironmentLoadConfiguration extends LoadConfiguration
{


    public function loadConfigurationFiles(Application $app, RepositoryContract $repository = NULL)
    {
    	$env = env('APP_ENV', "").".";

    	$files = $this->getConfigurationFiles($app);
    	$data = [];
		foreach ($files as $path => $key) {
			if(substr($key, 0, strlen($env)) == $env)
			{
				$key = substr($key, strlen($env));
				if(isset($data[$key]))
				{
					$data[$key] = $this->configurationMerge($data[$key], require $path);
					continue;
				}
			}
			if(isset($data[$key]))
			{
				$data[$key] = $this->configurationMerge($data[$key], require $path);
			}else
			{
				$data[$key] = require $path;
			}
		}
		$data = $this->searchForCopy($data, $data);
		if(isset($repository))
			foreach($data as $key => $value)
			{
				$repository->set($key, $value);
			}
		return $data;
    }
	public function searchForCopy(&$data, &$root)
	{
		foreach($data as $key=>$value)
		{
			if(is_array($value))
			{
				$data[$key] = $this->searchForCopy($value, $root);
			}else
			{
				if(is_string($value) && starts_with($value, '$copy:'))
				{
					$ref = substr($value, 6);
					$data[$key] = Arr::get($root, $ref);
					if(!array_key_exists($key, $data))
					{
						throw new \Exception('use $copy for config value to a reference that doesn\'t exist: '.$key.'=>'.$ref);
					}
				}
			}
		}
		return $data;
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
		$files = [];
		foreach($paths as $path)
		{
			foreach(Finder::create()->files()->name('*.php')->in($path) as $file) {
				$directory = $this->getNestedDirectory($file, $path);
				if(strlen($directory))
				{

					if(substr($directory, 0, strlen($env)) == $env)
					{
						$second[$file->getRealPath()] = $directory.basename($file->getRealPath(), '.php');
					}
					continue;
				}
				$files[$file->getRealPath()] = $directory.basename($file->getRealPath(), '.php');
			}

		}
		return $files + $second;
    }

}
