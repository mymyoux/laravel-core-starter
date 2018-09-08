<?php
namespace Core\Console\Commands\Phinx;
use Phinx\Migration\Manager;
use Phinx\Migration\MigrationInterface;

class PhinxManager extends Manager
{
	protected $countMigrations = 0;
	public function executeMigration($name, MigrationInterface $migration, $direction = MigrationInterface::UP, $fake = false)
	{
		$this->countMigrations++;
		parent::executeMigration($name, $migration, $direction, $fake);
	}
	public function getCountMigrations()
	{
		return $this->countMigrations;
	}
}
