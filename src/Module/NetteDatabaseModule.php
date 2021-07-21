<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Module;

use Codeception\Module\Db;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

final class NetteDatabaseModule extends Db
{

	public function _beforeSuite($settings = [])
	{
		$default = $this->config['cleanup'];
		if ($this->config['cleanupSuite'] ?? false === true) {
			$this->config['cleanup'] = true;
		}

		parent::_beforeSuite($settings);

		$this->config['cleanup'] = $default;
	}

	protected function readSqlForDatabases()
	{
		parent::readSqlForDatabases();

		/** @var NetteDIModule $module */
		$module = $this->getModule(NetteDIModule::class);

		foreach ($this->getDatabases() as $key => $config) {
			if ($config['doctrine'] ?? false === true) {
				$em = $module->getContainer()->getByType(EntityManagerInterface::class);

				$this->databasesSql[$key] = array_map(
					fn (string $sql) => $sql . ';',
					(new SchemaTool($em))->getCreateSchemaSql(
						$em->getMetadataFactory()->getAllMetadata()
					),
				);
			}
		}
	}

	protected function populateDatabases($configKey): void
	{
		parent::populateDatabases($configKey);

		/** @var NetteDIModule $module */
		$module = $this->getModule(NetteDIModule::class);
		$em = $module->getContainer()->getByType(EntityManagerInterface::class);

		foreach ($this->getDatabases() as $key => $config) {
			if (isset($config['fixtures']) && is_callable($config['fixtures'])) {
				$config['fixtures']($em);
			}
		}
	}

}
