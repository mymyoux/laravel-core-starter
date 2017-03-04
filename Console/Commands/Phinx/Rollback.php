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
use Phinx\Migration\Manager;
use Phinx\Config\Config;
use App;
use Request;

class Rollback extends PhinxCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'phinx:rollback {--target=} {--date=} {--force} {--skip-tables}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback tables';

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
        $environment = NULL;
        $version     = $this->option('target');
        $date        = $this->option('date');
        $force       = !!$this->option('force');

        $config = $this->getConfig();

        $output = new StreamOutput(fopen('php://stdout', 'w'));

        if (null === $environment) {
            $environment = $config->getDefaultEnvironment();
            $output->writeln('<comment>warning</comment> no environment specified, defaulting to: ' . $environment);
        } else {
            $output->writeln('<info>using environment</info> ' . $environment);
        }

        $envOptions = $config->getEnvironment($environment);
        if (isset($envOptions['adapter'])) {
            $output->writeln('<info>using adapter</info> ' . $envOptions['adapter']);
        }

        if (isset($envOptions['wrapper'])) {
            $output->writeln('<info>using wrapper</info> ' . $envOptions['wrapper']);
        }

        if (isset($envOptions['name'])) {
            $output->writeln('<info>using database</info> ' . $envOptions['name']);
        }
        
        $versionOrder = $this->getConfig()->getVersionOrder();
        $output->writeln('<info>ordering by </info>' . $versionOrder . " time");

        // rollback the specified environment
        if (null === $date) {
            $targetMustMatchVersion = true;
            $target = $version;
        } else {
            $targetMustMatchVersion = false;
            $target = $this->getTargetFromDate($date);
        }

        $start = microtime(true);
        $this->getManager()->rollback($environment, $target, $force, False && $targetMustMatchVersion);
        $end = microtime(true);

        $output->writeln('');
        $output->writeln('<comment>All Done. Took ' . sprintf('%.4fs', $end - $start) . '</comment>');

        if($this->getManager()->getCountMigrations() && !$this->option('skip-tables'))
        {
            $this->call("table:cache");
        }
    }
}
