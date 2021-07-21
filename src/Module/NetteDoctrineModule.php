<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Module;

use WebChemistry\Codeception\Attribute\NoDoctrineTransaction;
use WebChemistry\Codeception\Helper\CodeceptionHelper;
use Codeception\Module\Doctrine2;
use Codeception\TestInterface;
use Doctrine\ORM\EntityManagerInterface;

final class NetteDoctrineModule extends Doctrine2
{

	private bool $default;

	public function _depends(): array
	{
		return [];
	}

	public function _before(TestInterface $test): void
	{
		if (CodeceptionHelper::hasAttribute($test, NoDoctrineTransaction::class)) {
			$this->default = $this->config['cleanup'];
			$this->config['cleanup'] = false;
		}

		parent::_before($test);
	}

	public function _after(TestInterface $test): void
	{
		parent::_after($test);

		if (isset($this->default)) {
			$this->config['cleanup'] = $this->default;

			unset($this->default);
		}
	}

	protected function retrieveEntityManager(): void
	{
		/** @var NetteDIModule $module */
		$module = $this->getModule(NetteDIModule::class);
		$this->em = $module->getContainer()->getByType(EntityManagerInterface::class);
	}

}
