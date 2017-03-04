<?php

namespace Core\Console\Commands\Phinx;
use Db;
use Illuminate\Console\Command;
use Core\Util\ClassWriter;
use Schema;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Phinx\Console\PhinxApplication;
use Phinx\Migration\Manager;
use Phinx\Config\Config;
use App;
use Request;
use Phinx\Util\Util;

class Create extends PhinxCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'phinx:create {name} {--folder=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create migration';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    protected function getMigrationPath()
    {
        $config = $this->getConfig();
        $path = $config->getMigrationPaths()[0];
        return $path;
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $className = ucfirst(camel_case($this->argument('name')));
        
        $config = $this->getConfig();

        $path = $this->getMigrationPath();
        if(!file_exists($path))
        {
            if ($this->confirm('Create migrations directory?', True)) {
                  mkdir($path, 0755, true);
            }
        }
        $this->verifyMigrationDirectory($path);


        if (!Util::isValidPhinxClassName($className)) {
            throw new \InvalidArgumentException(sprintf(
                'The migration class name "%s" is invalid. Please use CamelCase format.',
                $className
            ));
        }

        $paths = $this->getMigrationPaths();
        foreach($paths as $p)
        {
            if (!Util::isUniqueMigrationClassName($className, $p)) {
                throw new \InvalidArgumentException(sprintf(
                    'The migration class name "%s" already exists',
                    $className
                ));
            }
        }

        // Compute the file path
        $fileName = Util::mapClassNameToFileName($className);
        $filePath = join_paths($path , $fileName);

        if (is_file($filePath)) {
            throw new \InvalidArgumentException(sprintf(
                'The file "%s" already exists',
                $filePath
            ));
        }


        // Get the alternative template and static class options from the config, but only allow one of them.
        $defaultAltTemplate = $this->getConfig()->getTemplateFile();
        $defaultCreationClassName = $this->getConfig()->getTemplateClass();
        if ($defaultAltTemplate && $defaultCreationClassName){
            throw new \InvalidArgumentException('Cannot define template:class and template:file at the same time');
        }

        // Get the alternative template and static class options from the command line, but only allow one of them.
        $altTemplate = NULL;
        $creationClassName = NULL;
        if($this->hasOption("template"))
        {
            $altTemplate = $this->getOption('template');
        }
        if($this->hasOption("class"))
        {
            $creationClassName = $this->getOption('class');
        }
        if ($altTemplate && $creationClassName) {
            throw new \InvalidArgumentException('Cannot use --template and --class at the same time');
        }

        // If no commandline options then use the defaults.
        if (!$altTemplate && !$creationClassName){
            $altTemplate = $defaultAltTemplate;
            $creationClassName = $defaultCreationClassName;
        }

        // Verify the alternative template file's existence.
        if ($altTemplate && !is_file($altTemplate)) {
            throw new \InvalidArgumentException(sprintf(
                'The alternative template file "%s" does not exist',
                $altTemplate
            ));
        }


         // Verify that the template creation class (or the aliased class) exists and that it implements the required interface.
        $aliasedClassName  = null;
        if ($creationClassName) {
            // Supplied class does not exist, is it aliased?
            if (!class_exists($creationClassName)) {
                $aliasedClassName = $this->getConfig()->getAlias($creationClassName);
                if ($aliasedClassName && !class_exists($aliasedClassName)) {
                    throw new \InvalidArgumentException(sprintf(
                        'The class "%s" via the alias "%s" does not exist',
                        $aliasedClassName,
                        $creationClassName
                    ));
                } elseif (!$aliasedClassName) {
                    throw new \InvalidArgumentException(sprintf(
                        'The class "%s" does not exist',
                        $creationClassName
                    ));
                }
            }

            // Does the class implement the required interface?
            if (!$aliasedClassName && !is_subclass_of($creationClassName, self::CREATION_INTERFACE)) {
                throw new \InvalidArgumentException(sprintf(
                    'The class "%s" does not implement the required interface "%s"',
                    $creationClassName,
                    self::CREATION_INTERFACE
                ));
            } elseif ($aliasedClassName && !is_subclass_of($aliasedClassName, self::CREATION_INTERFACE)) {
                throw new \InvalidArgumentException(sprintf(
                    'The class "%s" via the alias "%s" does not implement the required interface "%s"',
                    $aliasedClassName,
                    $creationClassName,
                    self::CREATION_INTERFACE
                ));
            }
        }





         // Use the aliased class.
        $creationClassName = $aliasedClassName ?: $creationClassName;

        // Determine the appropriate mechanism to get the template
        if ($creationClassName) {
            // Get the template from the creation class
            $creationClass = new $creationClassName($input, $output);
            $contents = $creationClass->getMigrationTemplate();
        } else {
            // Load the alternative template if it is defined.
            $contents = file_get_contents($altTemplate ?: $this->getMigrationTemplateFilename());
        }

        // inject the class names appropriate to this migration
        $classes = array(
            '$useClassName'  => $this->getConfig()->getMigrationBaseClassName(false),
            '$className'     => $className,
            '$version'       => Util::getVersionFromFileName($fileName),
            '$baseClassName' => $this->getConfig()->getMigrationBaseClassName(true),
            '$basename' => base_path(),
            '$autoload' => base_path('vendor/autoload.php')
        );
        $contents = strtr($contents, $classes);

        if (false === file_put_contents($filePath, $contents)) {
            throw new \RuntimeException(sprintf(
                'The file "%s" could not be written to',
                $path
            ));
        }

        // Do we need to do the post creation call to the creation class?
        if (isset($creationClass)) {
            $creationClass->postMigrationCreation($filePath, $className, $this->getConfig()->getMigrationBaseClassName());
        }

        $this->line('using migration base class' . $classes['$useClassName']);

        if (!empty($altTemplate)) {
            $this->line('using alternative template ' . $altTemplate);
        } elseif (!empty($creationClassName)) {
            $this->line('using template creation class ' . $creationClassName);
        } else {
            $this->line('using default template');
        }

        $this->info('Created ' . $filePath);
    }
}
