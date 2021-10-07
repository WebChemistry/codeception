<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Module;

use Codeception\Lib\Interfaces\DoctrineProvider;
use WebChemistry\Codeception\Attribute\Doctrine;
use WebChemistry\Codeception\Attribute\NoDoctrineTransaction;
use WebChemistry\Codeception\Helper\CodeceptionHelper;
use Codeception\Module\Doctrine2;
use Codeception\TestInterface;
use Doctrine\ORM\EntityManagerInterface;

final class NetteDoctrineModule extends Doctrine2
{

	private array $defaults;

	private bool $nextDatabaseCleanup = false;

	public function _depends(): array
	{
		return [];
	}

	public function _initialize()
	{
		/** @var NetteDatabaseModule $netteDatabaseModule */
		$netteDatabaseModule = $this->getModule(NetteDatabaseModule::class);

		$netteDatabaseModule->onBefore[] = function (TestInterface $test, NetteDatabaseModule $netteDatabaseModule): void {
			if ($this->nextDatabaseCleanup) {
				$netteDatabaseModule->_cleanupDatabase();

				$this->nextDatabaseCleanup = false;
			}
		};

		$netteDatabaseModule->onAfter[] = function (TestInterface $test, NetteDatabaseModule $netteDatabaseModule): void {
			$doctrine = CodeceptionHelper::getAttribute($test, Doctrine::class);
			if ($doctrine && $doctrine->transaction === false) {
				// clean up database after flush
				$this->nextDatabaseCleanup = true;
			}
		};
	}

	public function _setConfig($config)
	{
		parent::_setConfig($config);

		$this->defaults = $this->config;
	}

	public function _before(TestInterface $test): void
	{
		if ($doctrine = CodeceptionHelper::getAttribute($test, Doctrine::class)) {
			if ($doctrine->transaction !== null) {
				$this->config['cleanup'] = $doctrine->transaction;
			}

			if ($doctrine->purgeMode !== null) {
				$this->config['purge_mode'] = $doctrine->purgeMode;
			}
		}

		parent::_before($test);
	}

	public function _after(TestInterface $test): void
	{
		parent::_after($test);

		$this->config = $this->defaults;
	}

	protected function retrieveEntityManager(): void
	{
		/** @var NetteDIModule $module */
		$module = $this->getModule(NetteDIModule::class);

		$this->em = $module->getContainer()->getByType(EntityManagerInterface::class);
	}

}
