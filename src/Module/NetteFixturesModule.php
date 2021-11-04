<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Module;

use Codeception\Module;
use Codeception\TestInterface;
use LogicException;
use WebChemistry\Codeception\Helper\CodeceptionHelper;
use WebChemistry\Codeception\Module\Objects\CodeceptionFixtureInterface;

final class NetteFixturesModule extends Module
{

	/** @var CodeceptionFixtureInterface[] */
	private array $fixtures;

	public function _beforeSuite($settings = [])
	{
		/** @var NetteDIModule $module */
		$module = $this->getModule(NetteDIModule::class);

		foreach ($this->getFixtures() as $fixture) {
			$fixture->cleanup($module->getContainer());
			$fixture->load($module->getContainer());
		}
	}

	private function getDIModule(): NetteDIModule
	{
		$module = $this->getModule(NetteDIModule::class);
		assert($module instanceof NetteDIModule);

		return $module;
	}

	/**
	 * @return CodeceptionFixtureInterface[]
	 */
	public function getFixtures(): array
	{
		if (!isset($this->fixtures)) {
			$this->fixtures = CodeceptionHelper::loadClasses(
				self::class,
				$this->config ?? [],
				CodeceptionFixtureInterface::class,
			);
		}

		return $this->fixtures;
	}

}
