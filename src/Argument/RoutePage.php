<?php declare(strict_types = 1);

namespace WebChemistry\Codeception\Argument;

final class RoutePage
{

	/**
	 * @param mixed[] $parameters
	 */
	public function __construct(
		public string $destination,
		public array $parameters = [],
	)
	{
	}

}
