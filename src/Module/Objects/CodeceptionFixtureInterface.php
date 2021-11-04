<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Module\Objects;

use Nette\DI\Container;

interface CodeceptionFixtureInterface
{

	public function load(Container $container): void;

	public function cleanup(Container $container): void;

}
