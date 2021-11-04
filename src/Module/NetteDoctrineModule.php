<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Module;

use Codeception\Module\Doctrine2;
use Doctrine\ORM\EntityManagerInterface;
use WebChemistry\Codeception\Attribute\NoDoctrineTransaction;
use WebChemistry\Codeception\Helper\CodeceptionHelper;
use WebChemistry\Codeception\Module\Objects\CodeceptionFixtureInterface;

final class NetteDoctrineModule extends Doctrine2
{

	private array $defaults;

	public function _depends(): array
	{
		return [];
	}

	public function _initialize()
	{
	}

	public function _setConfig($config)
	{
		parent::_setConfig($config);

		$this->defaults = $this->config;
	}

	public function loadFixtures($fixtures, $append = true)
	{
		/** @var NetteDIModule $module */
		$module = $this->getModule(NetteDIModule::class);

		foreach (CodeceptionHelper::loadClasses(
			self::class,
			(array) $fixtures,
			CodeceptionFixtureInterface::class
		) as $fixture) {
			$fixture->load($module->getContainer());
		}
	}

	protected function retrieveEntityManager(): void
	{
		/** @var NetteDIModule $module */
		$module = $this->getModule(NetteDIModule::class);

		$this->em = $module->getContainer()->getByType(EntityManagerInterface::class);
	}

}
