<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Module\Objects;

use Nette\DI\Container;

interface CodeceptionBootingInterface
{

	/**
	 * @return CodeceptionDirectory[]
	 */
	public function getDirectories(): array;

	public function getTempDir(): string;

	public function createContainer(): Container;

}
