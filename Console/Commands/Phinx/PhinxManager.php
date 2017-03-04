<?php
namespace Core\Console\Commands\Phinx;
use Phinx\Migration\Manager;
use Phinx\Migration\MigrationInterface;

class PhinxManager extends Manager
{
	protected $countMigrations = 0;
	public function executeMigration($name, MigrationInterface $migration, $direction = MigrationInterface::UP)
	{
		$this->countMigrations++;
		parent::executeMigration($name, $migration, $direction);
	}
	public function getCountMigrations()
	{
		return $this->countMigrations;
	}
}
