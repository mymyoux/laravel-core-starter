<?php

namespace Core\Console\Commands\Phinx;
use Db;
use Illuminate\Console\Command;
use Core\Util\ClassWriter;
use Schema;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Phinx\Console\PhinxApplication;
//use Phinx\Migration\Manager;
use Phinx\Config\Config;
use App;
use Request;

abstract class PhinxCommand extends Command
{
    protected $config;
    protected $manager;
    public function getMigrationPaths()
    {
        $paths = [];
        $all_paths = $this->getConfig()->offsetGet('paths');
        foreach($all_paths as $key=>$path)
        {
            if(!in_array($key, ["default","migrations"]))
            {
                $paths[] = $path;
            }   
        }
        return $paths;
    }
    public function getConfig()
    {
        if(isset($this->config))
        {
            return $this->config;
        }
        $forged_config = config('database.phinx');
        if($this->hasOption('folder'))
        {
            $folder = $this->option('folder');
            if(!isset($folder))
            {
                $folder = $forged_config["paths"]["default"];
            }
            if(!isset($forged_config["paths"][$folder]))
            {
                     throw new \InvalidArgumentException(sprintf(
                    'The folder option "%s" is invalid. Please check your config file.',
                    $folder
                ));
            }
            $forged_config["paths"]["migrations"] = base_path($forged_config["paths"][$folder]);
        }else
        {
            $forged_config["paths"]["migrations"] = array_reduce(array_keys($forged_config["paths"]), function($previous, $item) use($forged_config)
            {
                if($item == "default")
                    return $previous;

                $previous[] = base_path($forged_config["paths"][$item]);
                return $previous;
            }, []);
        }
        $env = App::environment();
        $adapter = config("database.default");
        $pdo = config("database.connections.$adapter");
        $pdo["adapter"] = $adapter;
        $pdo["name"] = $pdo["database"];
        $pdo["user"] = $pdo["username"];
        $pdo["pass"] = $pdo["password"];

        $forged_config["environments"] = 
        [
            'default_migration_table'=>isset($forged_config['migration_table'])?$forged_config['migration_table']:"phinxlog",
            $env=>$pdo
        ];
        $config = new Config($forged_config);
        return $this->config = $config;
    }
    public function getManager()
    {
        if(isset($this->manager))
        {
            return $this->manager;
        }
        $config = $this->getConfig();
        $manager = new PhinxManager($config, new ArgvInput(Request::input()), new StreamOutput(fopen('php://stdout', 'w')));
        return $this->manager = $manager;
    }


    protected function verifyMigrationDirectory($path)
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException(sprintf(
                'Migration directory "%s" does not exist',
                $path
            ));
        }

        if (!is_writable($path)) {
            throw new \InvalidArgumentException(sprintf(
                'Migration directory "%s" is not writable',
                $path
            ));
        }
    }
    protected function verifySeedDirectory($path)
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException(sprintf(
                'Seed directory "%s" does not exist',
                $path
            ));
        }

        if (!is_writable($path)) {
            throw new \InvalidArgumentException(sprintf(
                'Seed directory "%s" is not writable',
                $path
            ));
        }
    }
}
