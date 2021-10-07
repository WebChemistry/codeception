<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Module;

use Codeception\Lib\ModuleContainer;
use Codeception\Module\Db;
use Codeception\TestInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Nette\Utils\Arrays;
use WeakMap;
use WebChemistry\Codeception\Attribute\Database;
use WebChemistry\Codeception\Helper\CodeceptionHelper;

final class NetteDatabaseModule extends Db
{

	/** @var mixed[] */
	private array $defaults = [];

	/** @var callable[] */
	public array $onBefore = [];

	/** @var callable[] */
	public array $onBeforeAfter = [];

	/** @var callable[] */
	public array $onAfter = [];

	public function __construct(ModuleContainer $moduleContainer, $config = null)
	{
		parent::__construct($moduleContainer, $config);
	}

	public function _setConfig($config)
	{
		parent::_setConfig($config);

		$this->defaults = $this->config;
	}

	public function _beforeSuite($settings = [])
	{
		$this->_cleanupDatabase();

		parent::_beforeSuite($settings);

		$this->config = $this->defaults;
	}

	public function _before(TestInterface $test)
	{
		Arrays::invoke($this->onBefore, $test, $this);

		parent::_before($test);

		Arrays::invoke($this->onBeforeAfter, $test, $this);
	}

	public function _cleanupDatabase(): void
	{
		$this->config['cleanup'] = true;
		$this->config['populate'] = true;
	}

	public function _after(TestInterface $test)
	{
		Arrays::invoke($this->onAfter, $test, $this);

		parent::_after($test);

		$this->config = $this->defaults;
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

		$config = $this->config;
		if (!isset($config['fixtures']) || !is_callable($config['fixtures'])) {
			return;
		}

		/** @var NetteDIModule $module */
		$module = $this->getModule(NetteDIModule::class);
		$em = $module->getContainer()->getByType(EntityManagerInterface::class);

		foreach ($this->getDatabases() as $key => $config) {
			$config['fixtures']($em);
		}
	}

}
