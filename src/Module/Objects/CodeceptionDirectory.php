<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Module\Objects;

use JetBrains\PhpStorm\Immutable;

final class CodeceptionDirectory
{

	public function __construct(
		#[Immutable]
		public string $name,
		#[Immutable]
		public string $path,
		#[Immutable]
		public bool $truncateAfterEachTest = true,
	)
	{
	}

}
