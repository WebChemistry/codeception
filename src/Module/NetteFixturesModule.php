<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Module;

use Codeception\Module;
use Codeception\TestInterface;

final class NetteFixturesModule extends Module
{

	public function _before(TestInterface $test): void
	{
		$container = $this->getDIModule()->getContainer();
		foreach ($this->config as $class) {
			if (method_exists($class, 'install')) {
				[$class, 'install']($container);
			}
		}
	}

	public function _after(TestInterface $test): void
	{
		$container = $this->getDIModule()->getContainer();
		foreach ($this->config as $class) {
			if (method_exists($class, 'uninstall')) {
				[$class, 'uninstall']($container);
			}
		}
	}

	private function getDIModule(): NetteDIModule
	{
		$module = $this->getModule(NetteDIModule::class);
		assert($module instanceof NetteDIModule);

		return $module;
	}

}
