<?php

namespace Core\Console\Commands\Phinx;
use DB;
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

class Migrate extends PhinxCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'phinx:migrate {--target=} {--date=} {--skip-tables}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate tables';

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
        $version     = $this->option('target');
        $environment = NULL;
        $date        = $this->option('date');

        $output = new StreamOutput(fopen('php://stdout', 'w'));

        if (null === $environment) {
            $environment = $this->getConfig()->getDefaultEnvironment();
            $output->writeln('<comment>warning</comment> no environment specified, defaulting to: ' . $environment);
        } else {
            $output->writeln('<info>using environment</info> ' . $environment);
        }

        $envOptions = $this->getConfig()->getEnvironment($environment);
        if (isset($envOptions['adapter'])) {
            $output->writeln('<info>using adapter</info> ' . $envOptions['adapter']);
        }

        if (isset($envOptions['wrapper'])) {
            $output->writeln('<info>using wrapper</info> ' . $envOptions['wrapper']);
        }

        if (isset($envOptions['name'])) {
            $output->writeln('<info>using database</info> ' . $envOptions['name']);
        } else {
            $output->writeln('<error>Could not determine database name! Please specify a database name in your config file.</error>');
            return 1;
        }

        if (isset($envOptions['table_prefix'])) {
            $output->writeln('<info>using table prefix</info> ' . $envOptions['table_prefix']);
        }
        if (isset($envOptions['table_suffix'])) {
            $output->writeln('<info>using table suffix</info> ' . $envOptions['table_suffix']);
        }

        // run the migrations
        $start = microtime(true);
        if (null !== $date) {
            $this->getManager()->migrateToDateTime($environment, new \DateTime($date));
        } else {
            $this->getManager()->migrate($environment, $version);
        }
        $end = microtime(true);

        $output->writeln('');
        $output->writeln('<comment>All Done. Took ' . sprintf('%.4fs', $end - $start) . '</comment>');
        //if migrations rebuild tables
        if($this->getManager()->getCountMigrations() && !$this->option('skip-tables'))
        {
            $this->call("table:cache");
        }
        return 0;
    }
}
